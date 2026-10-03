<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use JayI\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\UpdateConcreteAgentToolsTool;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Domains\Tool\Support\ToolName;
use JayI\Cortex\Tests\Fixtures\EchoCortexTool;
use JayI\Cortex\Tests\Fixtures\LockedEchoAgent;

beforeEach(function (): void {
    app(AgentRegistry::class)->register('locked-echo', LockedEchoAgent::class);
    app(ToolRegistry::class)->register('echo-cortex', EchoCortexTool::class);
});

it('reports a locked agent as prompt-overridable but not tool-overridable', function (): void {
    $registry = app(AgentRegistry::class);

    expect($registry->supportsOverrides('locked-echo'))->toBeTrue()
        ->and($registry->supportsToolOverrides('locked-echo'))->toBeFalse();
});

it('keeps the code toolset even when an override row holds tools', function (): void {
    // A row saved before the class was locked must not leak back in.
    ConcreteAgentOverrideModel::query()->create(['agent' => 'locked-echo', 'tools' => ['echo-cortex']]);

    $tools = array_map(ToolName::of(...), [...app(LockedEchoAgent::class)->tools()]);

    expect($tools)->toBe(['EchoTool'])
        ->and(app(AgentRegistry::class)->effectiveTools('locked-echo'))->toBe(['EchoTool']);
});

it('still applies a published prompt override', function (): void {
    app(CreateConcreteAgentVersionAction::class)->execute('locked-echo', ['content' => 'Overridden.', 'publish' => true]);
    app()->forgetScopedInstances();

    expect((string) app(LockedEchoAgent::class)->instructions())->toBe('Overridden.');
});

it('rejects toolset overrides over HTTP and MCP but allows clearing', function (): void {
    $this->putJson(route('cortex.concrete-agents.tools.update', 'locked-echo'), ['tools' => ['EchoTool']])
        ->assertJsonValidationErrors(['tools']);

    mcpTool(UpdateConcreteAgentToolsTool::class, ['agent' => 'locked-echo', 'tools' => ['EchoTool']])
        ->assertHasErrors();

    $this->putJson(route('cortex.concrete-agents.tools.update', 'locked-echo'), ['tools' => null])->assertOk();

    expect(ConcreteAgentOverrideModel::query()->value('tools'))->toBeNull();
});

it('shows the toolset as locked on the dashboard', function (): void {
    ValidateCsrfToken::except(['*']);
    app()->detectEnvironment(fn (): string => 'local');

    $this->get(route('atrium.cortex.concrete-agents.show', 'locked-echo'))
        ->assertOk()
        ->assertSee(__('cortex::cortex.locked'))
        ->assertDontSee('data-testid="save-tools"', false);

    $this->getJson(route('cortex.concrete-agents.show', 'locked-echo'))
        ->assertJsonPath('data.tools_overridable', false);
});
