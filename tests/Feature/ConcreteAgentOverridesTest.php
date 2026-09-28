<?php

declare(strict_types=1);

use JayI\Cortex\Actions\CreateConcreteAgentVersionAction;
use JayI\Cortex\Actions\DeleteConcreteAgentOverrideAction;
use JayI\Cortex\Actions\UpdateConcreteAgentToolsAction;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\EchoCortexTool;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Cortex\Tests\Fixtures\PlainAgent;
use JayI\Cortex\Tools\ToolName;
use JayI\Cortex\Tools\ToolRegistry;

beforeEach(function () {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
});

/**
 * A fresh agent instance with the per-request override memo cleared.
 *
 * @return list<string>
 */
function echoAgentToolNames(): array
{
    app()->forgetScopedInstances();

    return array_map(fn (mixed $tool): string => ToolName::of($tool), [...app(EchoAgent::class)->tools()]);
}

function echoAgentInstructions(): string
{
    app()->forgetScopedInstances();

    return (string) app(EchoAgent::class)->instructions();
}

it('falls back to the code-declared prompt', function () {
    expect(echoAgentInstructions())->toBe('Echo everything back.');
});

it('serves the published prompt override', function () {
    app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', ['content' => 'Overridden.', 'publish' => true]);

    expect(echoAgentInstructions())->toBe('Overridden.');
});

it('ignores unpublished prompt versions', function () {
    app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', ['content' => 'Draft.']);

    expect(echoAgentInstructions())->toBe('Echo everything back.');
});

it('resolves an overridden toolset from code tools and registered tools', function () {
    app(ToolRegistry::class)->register('echo-cortex', EchoCortexTool::class);

    app(UpdateConcreteAgentToolsAction::class)->execute('echo-agent', ['EchoTool', 'echo-cortex']);

    app()->forgetScopedInstances();
    $tools = [...app(EchoAgent::class)->tools()];

    expect($tools)->toHaveCount(2)
        ->and($tools[0])->toBeInstanceOf(EchoTool::class)
        ->and(ToolName::of($tools[1]))->toBe('echo-cortex-tool');
});

it('skips override names that match no tool', function () {
    ConcreteAgentOverride::query()->create(['agent' => 'echo-agent', 'tools' => ['EchoTool', 'gone']]);

    expect(echoAgentToolNames())->toBe(['EchoTool']);
});

it('uses the code toolset when tools are not overridden', function () {
    app(UpdateConcreteAgentToolsAction::class)->execute('echo-agent', null);

    expect(echoAgentToolNames())->toBe(['EchoTool']);
});

it('runs with no tools when the override is empty', function () {
    app(UpdateConcreteAgentToolsAction::class)->execute('echo-agent', []);

    expect(echoAgentToolNames())->toBe([]);
});

it('restores the code prompt and tools when the override is deleted', function () {
    app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', ['content' => 'Overridden.', 'publish' => true]);
    $override = app(UpdateConcreteAgentToolsAction::class)->execute('echo-agent', []);

    expect(echoAgentInstructions())->toBe('Overridden.')
        ->and(echoAgentToolNames())->toBe([]);

    app(DeleteConcreteAgentOverrideAction::class)->execute($override);

    expect(echoAgentInstructions())->toBe('Echo everything back.')
        ->and(echoAgentToolNames())->toBe(['EchoTool']);
});

it('uses code for an agent class that is not registered', function () {
    // Overrides are keyed by registered name, so a row under the class's
    // derived name does nothing while the class is registered as another.
    app()->forgetInstance(AgentRegistry::class);
    ConcreteAgentOverride::query()->create(['agent' => 'echo-agent', 'tools' => []]);

    expect(echoAgentToolNames())->toBe(['EchoTool'])
        ->and(echoAgentInstructions())->toBe('Echo everything back.');
});

it('leaves agents without the trait untouched', function () {
    app(AgentRegistry::class)->register('plain', PlainAgent::class);
    app(CreateConcreteAgentVersionAction::class)->execute('plain', ['content' => 'Ignored.', 'publish' => true]);
    app(UpdateConcreteAgentToolsAction::class)->execute('plain', []);

    app()->forgetScopedInstances();
    $agent = app(PlainAgent::class);

    expect($agent->instructions())->toBe('Plain instructions.')
        ->and([...$agent->tools()])->toHaveCount(1);
});
