<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use JayI\Cortex\Actions\CreateVirtualAgentAction;
use JayI\Cortex\Actions\CreateVirtualAgentVersionAction;
use JayI\Cortex\Actions\DeleteVirtualAgentAction;
use JayI\Cortex\Actions\ListVirtualAgentsAction;
use JayI\Cortex\Actions\ListVirtualAgentVersionsAction;
use JayI\Cortex\Actions\PublishVirtualAgentVersionAction;
use JayI\Cortex\Actions\ShowVirtualAgentAction;
use JayI\Cortex\Actions\ShowVirtualAgentVersionAction;
use JayI\Cortex\Actions\UpdateVirtualAgentAction;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Cortex\Tools\ToolRegistry;

it('creates an agent with its prompt published as version 1', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
    $sub = VirtualAgent::factory()->create(['slug' => 'researcher']);

    $agent = app(CreateVirtualAgentAction::class)->execute([
        'name' => 'Coordinator',
        'slug' => 'coordinator',
        'instructions' => 'Coordinate the team.',
        'provider' => 'anthropic',
        'model' => 'claude-sonnet-5',
        'settings' => ['temperature' => 0.3],
        'tools' => ['echo'],
        'sub_agents' => ['researcher'],
        'concrete_sub_agents' => ['echo-agent'],
    ]);

    expect($agent->tools)->toBe(['echo'])
        ->and($agent->publishedVersion?->version)->toBe(1)
        ->and($agent->publishedVersion?->content)->toBe('Coordinate the team.')
        ->and($agent->versions()->count())->toBe(1)
        ->and($agent->subAgents->pluck('slug')->all())->toBe([$sub->slug])
        ->and($agent->concrete_sub_agents)->toBe(['echo-agent'])
        ->and($agent->provider)->toBe('anthropic')
        ->and($agent->settings)->toBe(['temperature' => 0.3]);
});

it('saves changed instructions as a new published version', function () {
    $agent = VirtualAgent::factory()->published('First.')->create();

    $updated = app(UpdateVirtualAgentAction::class)->execute($agent, ['instructions' => 'Second.']);

    expect($updated->publishedVersion?->version)->toBe(2)
        ->and($updated->publishedVersion?->content)->toBe('Second.')
        ->and($updated->versions()->count())->toBe(2);
});

it('leaves the version history alone when the instructions are unchanged', function () {
    $agent = VirtualAgent::factory()->published('Same.')->create();

    $updated = app(UpdateVirtualAgentAction::class)->execute($agent, ['instructions' => 'Same.', 'name' => 'Renamed']);

    expect($updated->name)->toBe('Renamed')
        ->and($updated->versions()->count())->toBe(1);
});

it('updates an agent with whole-list sync semantics', function () {
    $agent = VirtualAgent::factory()->published()->create(['tools' => ['old-tool']]);
    $agent->subAgents()->attach(VirtualAgent::factory()->create());
    app(ToolRegistry::class)->register('echo', EchoTool::class);

    $updated = app(UpdateVirtualAgentAction::class)->execute($agent, [
        'tools' => ['echo'],
        'sub_agents' => [],
        'concrete_sub_agents' => [],
    ]);

    expect($updated->tools)->toBe(['echo'])
        ->and($updated->subAgents)->toHaveCount(0)
        ->and($updated->concrete_sub_agents)->toBe([]);
});

it('rejects sub-agent cycles', function () {
    $a = VirtualAgent::factory()->create(['slug' => 'agent-a']);
    $b = VirtualAgent::factory()->create(['slug' => 'agent-b']);
    $a->subAgents()->attach($b);

    app(UpdateVirtualAgentAction::class)->execute($b, ['sub_agents' => ['agent-a']]);
})->throws(ValidationException::class, 'circular');

it('rejects an agent as its own sub-agent', function () {
    $agent = VirtualAgent::factory()->create(['slug' => 'self']);

    app(UpdateVirtualAgentAction::class)->execute($agent, ['sub_agents' => ['self']]);
})->throws(ValidationException::class, 'circular');

it('deletes an agent with its versions and sub-agent links', function () {
    $agent = VirtualAgent::factory()->published()->create();
    $agent->subAgents()->attach($sub = VirtualAgent::factory()->create());

    app(DeleteVirtualAgentAction::class)->execute($agent);

    expect(VirtualAgent::query()->whereKey($agent->getKey())->exists())->toBeFalse()
        ->and(VirtualAgentVersion::query()->count())->toBe(0)
        ->and($sub->refresh()->parentAgents)->toHaveCount(0);
});

it('lists and shows agents with relations', function () {
    $agent = VirtualAgent::factory()->published()->create();

    $shown = app(ShowVirtualAgentAction::class)->execute($agent);

    expect(app(ListVirtualAgentsAction::class)->execute()->total())->toBe(1)
        ->and($shown->relationLoaded('subAgents'))->toBeTrue()
        ->and($shown->relationLoaded('publishedVersion'))->toBeTrue();
});

it('numbers versions and publishes only when asked', function () {
    $agent = VirtualAgent::factory()->published('v1')->create();

    $v2 = app(CreateVirtualAgentVersionAction::class)->execute($agent, ['content' => 'v2']);
    $v3 = app(CreateVirtualAgentVersionAction::class)->execute($agent, ['content' => 'v3', 'publish' => true]);

    expect($v2->version)->toBe(2)
        ->and($v3->version)->toBe(3)
        ->and($agent->refresh()->publishedVersion?->version)->toBe(3);
});

it('republishes an earlier version', function () {
    $agent = VirtualAgent::factory()->published('v1')->create();
    app(CreateVirtualAgentVersionAction::class)->execute($agent, ['content' => 'v2', 'publish' => true]);

    $restored = app(PublishVirtualAgentVersionAction::class)->execute($agent, 1);

    expect($restored->publishedVersion?->content)->toBe('v1');
});

it('lists and shows versions', function () {
    $agent = VirtualAgent::factory()->published('v1')->create();
    app(CreateVirtualAgentVersionAction::class)->execute($agent, ['content' => 'v2']);

    expect(app(ListVirtualAgentVersionsAction::class)->execute($agent)->pluck('version')->all())->toBe([2, 1])
        ->and(app(ShowVirtualAgentVersionAction::class)->execute($agent, 2)->content)->toBe('v2');
});
