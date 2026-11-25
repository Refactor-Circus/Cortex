<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowSettingsSummaryAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\SettingsSummaryShowingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\SettingsSummaryShownActionEvent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;

/**
 * @return array{providers: list<array{name: string, models: list<string>}>, tool_count: int, agent_count: int, server_count: int, cache_enabled: bool}
 */
function showSettingsSummary(): array
{
    return app(ShowSettingsSummaryAction::class)->execute();
}

it('lists the configured providers with their models', function () {
    config()->set('cortex.providers', [
        'anthropic' => ['claude-sonnet-5-5', 'claude-opus-5-5'],
        'openai' => ['gpt-5'],
    ]);

    expect(showSettingsSummary()['providers'])->toBe([
        ['name' => 'anthropic', 'models' => ['claude-sonnet-5-5', 'claude-opus-5-5']],
        ['name' => 'openai', 'models' => ['gpt-5']],
    ]);
});

it('lists no providers when none are configured', function (mixed $configured) {
    config()->set('cortex.providers', $configured);

    expect(showSettingsSummary()['providers'])->toBe([]);
})->with([
    'empty' => [[]],
    'unset' => [null],
]);

it('counts the registered tools, agents and servers', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    app(AgentRegistry::class)->register('echo', EchoAgent::class);
    app(AgentRegistry::class)->register('echo-two', EchoAgent::class);
    app(McpServerRegistry::class)->register('echo', EchoServer::class);

    $summary = showSettingsSummary();

    expect($summary['tool_count'])->toBe(count(app(ToolRegistry::class)->names()))
        ->and($summary['agent_count'])->toBe(count(app(AgentRegistry::class)->names()))
        ->and($summary['server_count'])->toBe(count(app(McpServerRegistry::class)->names()))
        ->and(app(ToolRegistry::class)->names())->toContain('echo')
        ->and(app(AgentRegistry::class)->names())->toContain('echo', 'echo-two')
        ->and(app(McpServerRegistry::class)->names())->toContain('echo');
});

it('counts one more of each kind as each is registered', function () {
    $before = showSettingsSummary();

    app(ToolRegistry::class)->register('echo-extra', EchoTool::class);
    app(AgentRegistry::class)->register('echo-extra', EchoAgent::class);
    app(McpServerRegistry::class)->register('echo-extra', EchoServer::class);

    $after = showSettingsSummary();

    expect($after['tool_count'])->toBe($before['tool_count'] + 1)
        ->and($after['agent_count'])->toBe($before['agent_count'] + 1)
        ->and($after['server_count'])->toBe($before['server_count'] + 1);
});

it('reports whether the cache is enabled', function (bool $enabled) {
    config()->set('cortex.cache.enabled', $enabled);

    expect(showSettingsSummary()['cache_enabled'])->toBe($enabled);
})->with([
    'enabled' => [true],
    'disabled' => [false],
]);

it('dispatches the showing and shown events', function () {
    Event::fake([SettingsSummaryShowingActionEvent::class, SettingsSummaryShownActionEvent::class]);

    $summary = showSettingsSummary();

    Event::assertDispatched(SettingsSummaryShowingActionEvent::class);
    Event::assertDispatched(SettingsSummaryShownActionEvent::class, fn (SettingsSummaryShownActionEvent $event): bool => $event->summary === $summary);
});
