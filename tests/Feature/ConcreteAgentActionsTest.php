<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JayI\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentVersionsAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\PublishConcreteAgentVersionAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\ShowConcreteAgentAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Services\AgentFactory;
use JayI\Cortex\Domains\VirtualAgent\Support\DbAgent;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\EchoCortexTool;

beforeEach(function () {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
});

it('lists agents without an override', function () {
    $agents = app(ListConcreteAgentsAction::class)->execute();

    expect($agents)->toHaveCount(1)
        ->and($agents[0]['name'])->toBe('echo-agent')
        ->and($agents[0]['override'])->toBeNull()
        ->and($agents[0]['tools'])->toBe(['EchoTool'])
        ->and($agents[0]['default_tools'])->toBe(['EchoTool']);
});

it('lists agents with their override', function () {
    app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', ['content' => 'Overridden.', 'publish' => true]);

    $agents = app(ListConcreteAgentsAction::class)->execute();

    expect($agents[0]['override'])->toBeInstanceOf(ConcreteAgentOverrideModel::class)
        ->and($agents[0]['override']?->publishedVersion?->version)->toBe(1)
        ->and($agents[0]['instructions'])->toBe('Overridden.');
});

it('shows an agent with its defaults and live configuration', function () {
    app(UpdateConcreteAgentToolsAction::class)->execute('echo-agent', []);
    app()->forgetScopedInstances();

    $agent = app(ShowConcreteAgentAction::class)->execute('echo-agent');

    expect($agent['name'])->toBe('echo-agent')
        ->and($agent['class'])->toBe(EchoAgent::class)
        ->and($agent['overridable'])->toBeTrue()
        ->and($agent['default_instructions'])->toBe('Echo everything back.')
        ->and($agent['default_tools'])->toBe(['EchoTool'])
        ->and($agent['tools'])->toBe([])
        ->and($agent['override']?->tools)->toBe([]);
});

it('only accepts code tools and registered tools in a toolset override', function () {
    app(ToolRegistry::class)->register('echo-cortex', EchoCortexTool::class);

    $rules = UpdateConcreteAgentToolsAction::rules('echo-agent');

    expect(Validator::make(['tools' => ['EchoTool', 'echo-cortex']], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['tools' => null], $rules)->passes())->toBeTrue()
        ->and(Validator::make([], $rules)->passes())->toBeFalse();

    Validator::validate(['tools' => ['missing']], $rules);
})->throws(ValidationException::class);

it('numbers versions and publishes on request', function () {
    app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', ['content' => 'v1']);
    $second = app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', ['content' => 'v2']);

    $override = ConcreteAgentOverrideModel::query()->where('agent', 'echo-agent')->firstOrFail();

    expect($second->version)->toBe(2)
        ->and($override->published_version_id)->toBeNull()
        ->and(app(ListConcreteAgentVersionsAction::class)->execute($override)->pluck('version')->all())->toBe([2, 1]);

    $published = app(PublishConcreteAgentVersionAction::class)->execute($override, 1);

    expect($published->publishedVersion?->content)->toBe('v1');
});

it('runs a concrete agent', function () {
    EchoAgent::fake(['Hi back.']);

    $response = app(RunConcreteAgentAction::class)->execute('echo-agent', 'Hi');

    expect($response->text)->toBe('Hi back.');
    EchoAgent::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'Hi');
});

it('attaches concrete sub-agents to a virtual agent', function () {
    $agent = VirtualAgentModel::factory()->published()->create(['concrete_sub_agents' => ['echo-agent']]);

    $tools = [...app(AgentFactory::class)->make($agent)->tools()];

    expect($tools)->toHaveCount(1)
        ->and($tools[0])->toBeInstanceOf(EchoAgent::class);
});

it('names virtual sub-agents by their slug', function () {
    $parent = VirtualAgentModel::factory()->published()->create();
    $parent->subAgents()->attach(VirtualAgentModel::factory()->published()->create(['slug' => 'researcher', 'description' => 'Finds things.']));

    $tools = [...app(AgentFactory::class)->make($parent->fresh())->tools()];

    expect($tools[0])->toBeInstanceOf(DbAgent::class)
        ->and($tools[0]->name())->toBe('researcher')
        ->and($tools[0]->description())->toBe('Finds things.');
});

it('reports an overridden toolset by the names it was saved under', function () {
    app(ToolRegistry::class)->register('echo-cortex', EchoCortexTool::class);
    app(UpdateConcreteAgentToolsAction::class)->execute('echo-agent', ['echo-cortex', 'retired-tool']);
    app()->forgetScopedInstances();

    // The registered name, not the tool's own `echo-cortex-tool`, so the
    // reported list round-trips into the next override; unresolvable names
    // are dropped just as they are at run time.
    expect(app(ShowConcreteAgentAction::class)->execute('echo-agent')['tools'])->toBe(['echo-cortex']);
});
