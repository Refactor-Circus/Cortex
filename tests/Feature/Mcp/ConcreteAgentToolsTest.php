<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Mcp\Tools\CreateConcreteAgentVersionTool;
use JayI\Cortex\Mcp\Tools\DeleteConcreteAgentOverrideTool;
use JayI\Cortex\Mcp\Tools\ListConcreteAgentsTool;
use JayI\Cortex\Mcp\Tools\ListConcreteAgentVersionsTool;
use JayI\Cortex\Mcp\Tools\PublishConcreteAgentVersionTool;
use JayI\Cortex\Mcp\Tools\RunConcreteAgentTool;
use JayI\Cortex\Mcp\Tools\ShowConcreteAgentTool;
use JayI\Cortex\Mcp\Tools\UpdateConcreteAgentToolsTool;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;
use JayI\Cortex\Tests\Fixtures\EchoAgent;

beforeEach(function () {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
});

it('lists concrete agents in a data envelope', function () {
    mcpTool(ListConcreteAgentsTool::class)
        ->assertOk()
        ->assertStructuredContent(
            fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->where('data.0.name', 'echo-agent')
                ->etc(),
        );
});

it('shows a concrete agent with parity to the http payload', function () {
    $http = $this->getJson(route('cortex.concrete-agents.show', 'echo-agent'))->json('data');

    mcpTool(ShowConcreteAgentTool::class, ['agent' => 'echo-agent'])
        ->assertOk()
        ->assertStructuredContent($http);
});

it('errors not found for unregistered agents', function () {
    mcpTool(ShowConcreteAgentTool::class, ['agent' => 'missing'])->assertHasErrors(['Not found.']);
    mcpTool(RunConcreteAgentTool::class, ['agent' => 'missing', 'input' => 'Hi'])->assertHasErrors(['Not found.']);
});

it('creates, lists and publishes prompt versions', function () {
    mcpTool(CreateConcreteAgentVersionTool::class, ['agent' => 'echo-agent', 'content' => 'v1'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json->where('version', 1)->etc());
    mcpTool(CreateConcreteAgentVersionTool::class, ['agent' => 'echo-agent', 'content' => 'v2'])->assertOk();

    mcpTool(ListConcreteAgentVersionsTool::class, ['agent' => 'echo-agent'])
        ->assertOk()
        ->assertStructuredContent(
            fn (AssertableJson $json) => $json->count('data', 2)->where('data.0.version', 2)->etc(),
        );

    mcpTool(PublishConcreteAgentVersionTool::class, ['agent' => 'echo-agent', 'version' => 2])
        ->assertOk()
        ->assertStructuredContent(
            fn (AssertableJson $json) => $json->where('published_version', 2)->where('published_content', 'v2')->etc(),
        );
});

it('errors not found for an unknown version', function () {
    mcpTool(CreateConcreteAgentVersionTool::class, ['agent' => 'echo-agent', 'content' => 'v1'])->assertOk();

    mcpTool(PublishConcreteAgentVersionTool::class, ['agent' => 'echo-agent', 'version' => 9])
        ->assertHasErrors(['Not found.']);
});

it('sets and clears the toolset override', function () {
    mcpTool(UpdateConcreteAgentToolsTool::class, ['agent' => 'echo-agent', 'tools' => []])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json->where('tools', [])->etc());

    mcpTool(UpdateConcreteAgentToolsTool::class, ['agent' => 'echo-agent', 'tools' => null])->assertOk();

    expect(ConcreteAgentOverride::query()->where('agent', 'echo-agent')->value('tools'))->toBeNull();
});

it('rejects unknown tools in the override', function () {
    mcpTool(UpdateConcreteAgentToolsTool::class, ['agent' => 'echo-agent', 'tools' => ['missing']])
        ->assertHasErrors();
});

it('deletes every override of an agent', function () {
    mcpTool(CreateConcreteAgentVersionTool::class, ['agent' => 'echo-agent', 'content' => 'v1', 'publish' => true])->assertOk();

    mcpTool(DeleteConcreteAgentOverrideTool::class, ['agent' => 'echo-agent'])
        ->assertOk()
        ->assertSee('Concrete agent overrides deleted.');

    expect(ConcreteAgentOverride::query()->count())->toBe(0)
        ->and(ConcreteAgentOverrideVersion::query()->count())->toBe(0);
});

it('errors not found deleting a missing override', function () {
    mcpTool(DeleteConcreteAgentOverrideTool::class, ['agent' => 'echo-agent'])->assertHasErrors(['Not found.']);
});

it('runs a concrete agent', function () {
    EchoAgent::fake(['Hi back.']);

    mcpTool(RunConcreteAgentTool::class, ['agent' => 'echo-agent', 'input' => 'Hi'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json->where('text', 'Hi back.')->etc());

    EchoAgent::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'Hi');
});
