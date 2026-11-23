<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;

it('answers the settings summary', function () {
    config()->set('cortex.providers', ['anthropic' => ['claude-sonnet-5-5', 'claude-opus-5-5']]);
    config()->set('cortex.cache.enabled', false);
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    app(AgentRegistry::class)->register('echo', EchoAgent::class);
    app(McpServerRegistry::class)->register('echo', EchoServer::class);

    $this->getJson(route('cortex.settings-summary.show'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'providers' => [['name' => 'anthropic', 'models' => ['claude-sonnet-5-5', 'claude-opus-5-5']]],
            'tool_count' => count(app(ToolRegistry::class)->names()),
            'agent_count' => count(app(AgentRegistry::class)->names()),
            'server_count' => count(app(McpServerRegistry::class)->names()),
            'cache_enabled' => false,
        ]]);
});

it('answers typed fields', function () {
    $response = $this->getJson(route('cortex.settings-summary.show'))->assertOk();

    expect($response->json('data.providers'))->toBeArray()
        ->and($response->json('data.tool_count'))->toBeInt()
        ->and($response->json('data.agent_count'))->toBeInt()
        ->and($response->json('data.server_count'))->toBeInt()
        ->and($response->json('data.cache_enabled'))->toBeBool();
});

it('answers an empty provider list when none are configured', function () {
    config()->set('cortex.providers', []);

    $this->getJson(route('cortex.settings-summary.show'))
        ->assertOk()
        ->assertJsonPath('data.providers', []);
});

it('is reachable by guests and signed-in users alike, as providers are', function () {
    $this->getJson(route('cortex.providers.index'))->assertOk();
    $this->getJson(route('cortex.settings-summary.show'))->assertOk();

    $this->actingAs(new GenericUser(['id' => 1]));

    $this->getJson(route('cortex.settings-summary.show'))->assertOk();
});

it('is served at settings-summary under the route prefix', function () {
    expect(route('cortex.settings-summary.show', absolute: false))
        ->toBe('/'.trim((string) config('cortex.routes.prefix'), '/').'/settings-summary');
});
