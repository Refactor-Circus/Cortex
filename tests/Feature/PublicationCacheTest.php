<?php

declare(strict_types=1);

use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use JayI\Cortex\Actions\CreateConcreteAgentVersionAction;
use JayI\Cortex\Actions\CreateMcpInstructionVersionAction;
use JayI\Cortex\Actions\CreateToolDescriptionVersionAction;
use JayI\Cortex\Actions\CreateVirtualAgentVersionAction;
use JayI\Cortex\Actions\DeleteMcpInstructionAction;
use JayI\Cortex\Actions\DeleteToolDescriptionAction;
use JayI\Cortex\Actions\PublishConcreteAgentVersionAction;
use JayI\Cortex\Actions\PublishMcpInstructionVersionAction;
use JayI\Cortex\Actions\PublishToolDescriptionVersionAction;
use JayI\Cortex\Actions\PublishVirtualAgentVersionAction;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Mcp\McpInstructionOverrides;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\McpInstruction;
use JayI\Cortex\Models\ToolDescription;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Runtime\AgentFactory;
use JayI\Cortex\Support\PublicationCache;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Cortex\Tools\ToolRegistry;

function freshAgentInstructions(VirtualAgent $agent): string
{
    return app(AgentFactory::class)->make($agent->fresh(['publishedVersion', 'subAgents']))->instructions();
}

it('caches the published prompt until a new version is published', function () {
    $agent = VirtualAgent::factory()->create();
    app(CreateVirtualAgentVersionAction::class)->execute($agent, ['content' => 'v1 instructions', 'publish' => true]);

    expect(freshAgentInstructions($agent))->toBe('v1 instructions');

    // A write that bypasses the publishing actions is not seen — the cached
    // copy keeps serving until an action invalidates it.
    $rogue = $agent->versions()->create(['version' => 2, 'content' => 'rogue instructions']);
    $agent->published_version_id = $rogue->getKey();
    $agent->save();

    expect(freshAgentInstructions($agent))->toBe('v1 instructions');

    app(PublishVirtualAgentVersionAction::class)->execute($agent->fresh(), 2);

    expect(freshAgentInstructions($agent))->toBe('rogue instructions');
});

it('caches concrete agent overrides until one is published', function () {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);

    $freshInstructions = function (): string {
        app()->forgetScopedInstances();

        return (string) app(EchoAgent::class)->instructions();
    };

    app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', ['content' => 'first', 'publish' => true]);

    expect($freshInstructions())->toBe('first');

    // Direct write bypassing the actions: cache keeps serving the old map.
    $override = ConcreteAgentOverride::query()->where('agent', 'echo-agent')->firstOrFail();
    $rogue = $override->versions()->create(['version' => 2, 'content' => 'rogue']);
    $override->published_version_id = $rogue->getKey();
    $override->save();

    expect($freshInstructions())->toBe('first');

    app(PublishConcreteAgentVersionAction::class)->execute($override->fresh(), 2);

    expect($freshInstructions())->toBe('rogue');
});

it('caches the tool description override map until a version is published', function () {
    $registry = app(ToolRegistry::class);
    $registry->register('echo', EchoTool::class);

    app(CreateToolDescriptionVersionAction::class)->execute('echo', ['content' => 'first', 'publish' => true]);

    $freshDescription = function () use ($registry): string {
        app()->forgetScopedInstances();

        return (string) $registry->get('echo')->description();
    };

    expect($freshDescription())->toBe('first');

    // Direct write bypassing the actions: cache keeps serving the old map.
    $description = ToolDescription::query()->where('tool', 'echo')->firstOrFail();
    $rogue = $description->versions()->create(['version' => 2, 'content' => 'rogue']);
    $description->published_version_id = $rogue->getKey();
    $description->save();

    expect($freshDescription())->toBe('first');

    app(PublishToolDescriptionVersionAction::class)->execute($description->fresh(), 2);

    expect($freshDescription())->toBe('rogue');
});

it('reads straight from the database when caching is disabled', function () {
    config()->set('cortex.cache.enabled', false);

    $agent = VirtualAgent::factory()->create();
    app(CreateVirtualAgentVersionAction::class)->execute($agent, ['content' => 'v1 instructions', 'publish' => true]);

    expect(freshAgentInstructions($agent))->toBe('v1 instructions');

    // Even a rogue write bypassing the actions is visible immediately.
    $rogue = $agent->versions()->create(['version' => 2, 'content' => 'rogue instructions']);
    $agent->published_version_id = $rogue->getKey();
    $agent->save();

    expect(freshAgentInstructions($agent))->toBe('rogue instructions');
});

it('invalidates the override map when an override is deleted', function () {
    $registry = app(ToolRegistry::class);
    $registry->register('echo', EchoTool::class);

    app(CreateToolDescriptionVersionAction::class)->execute('echo', ['content' => 'override', 'publish' => true]);

    app()->forgetScopedInstances();
    expect((string) $registry->get('echo')->description())->toBe('override');

    app(DeleteToolDescriptionAction::class)->execute(ToolDescription::query()->where('tool', 'echo')->firstOrFail());

    app()->forgetScopedInstances();
    expect((string) $registry->get('echo')->description())->toBe('Echoes back the given message.');
});

it('caches the mcp instruction override map until a version is published', function () {
    app(CreateMcpInstructionVersionAction::class)->execute('cortex', ['content' => 'first', 'publish' => true]);

    $freshInstructions = function (): ?string {
        app()->forgetScopedInstances();

        return app(McpInstructionOverrides::class)->for('cortex');
    };

    expect($freshInstructions())->toBe('first');

    // Direct write bypassing the actions: cache keeps serving the old map.
    $instruction = McpInstruction::query()->where('server', 'cortex')->firstOrFail();
    $rogue = $instruction->versions()->create(['version' => 2, 'content' => 'rogue']);
    $instruction->published_version_id = $rogue->getKey();
    $instruction->save();

    expect($freshInstructions())->toBe('first');

    app(PublishMcpInstructionVersionAction::class)->execute($instruction->fresh(), 2);

    expect($freshInstructions())->toBe('rogue');
});

it('shares a redis hash tag between each cache key and its flexible created twin', function () {
    $cache = app(PublicationCache::class);

    $keys = [
        $cache->toolDescriptionsKey(),
        $cache->mcpInstructionsKey(),
        $cache->concreteAgentsKey(),
        $cache->virtualAgentKey('7'),
    ];

    foreach ($keys as $key) {
        // Redis cluster hashes only the first {...} segment of a key, so the
        // key and its flexible() created twin must expose an identical tag.
        preg_match('/\{[^}]+\}/', $key, $keyTag);
        preg_match('/\{[^}]+\}/', 'illuminate:cache:flexible:created:'.$key, $twinTag);

        expect($keyTag)->not->toBeEmpty()
            ->and($twinTag[0])->toBe($keyTag[0]);
    }
});

it('falls back to the callback when the cache backend fails', function () {
    config()->set('cortex.cache.store', 'redis');

    $store = Mockery::mock(RedisStore::class);
    $repository = Mockery::mock(Repository::class);
    $repository->shouldReceive('getStore')->andReturn($store);
    $repository->shouldReceive('flexible')->andThrow(
        new TypeError('array_map(): Argument #2 ($array) must be of type array, false given'),
    );

    Cache::shouldReceive('store')->with('redis')->andReturn($repository);

    expect(app(PublicationCache::class)->remember('key', fn (): string => 'from-db'))->toBe('from-db');
});

it('invalidates the mcp instruction map when an override is deleted', function () {
    app(CreateMcpInstructionVersionAction::class)->execute('cortex', ['content' => 'override', 'publish' => true]);

    app()->forgetScopedInstances();
    expect(app(McpInstructionOverrides::class)->for('cortex'))->toBe('override');

    app(DeleteMcpInstructionAction::class)->execute(McpInstruction::query()->where('server', 'cortex')->firstOrFail());

    app()->forgetScopedInstances();
    expect(app(McpInstructionOverrides::class)->for('cortex'))->toBeNull();
});
