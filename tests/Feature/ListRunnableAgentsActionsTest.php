<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverridePolicy;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListRunnableAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\RunnableAgentsListedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\RunnableAgentsListingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;

/**
 * Lets every virtual agent be run except those whose slug starts with
 * "locked".
 */
final class RunnableHidingVirtualAgentPolicy extends VirtualAgentPolicy
{
    public function run(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return ! str_starts_with((string) $agent->slug, 'locked');
    }
}

/**
 * Refuses to run each concrete agent that has a saved override, so a test
 * can tell the saved override from the unsaved stand-in the action checks.
 */
final class RunnableHidingConcreteAgentOverridePolicy extends ConcreteAgentOverridePolicy
{
    public function run(?Authenticatable $user, ConcreteAgentOverrideModel $override): bool
    {
        return ! $override->exists;
    }
}

/**
 * @return list<array{type: string, key: string, name: string}>
 */
function listRunnableAgents(?Authenticatable $user = null): array
{
    return app(ListRunnableAgentsAction::class)->execute($user);
}

it('lists every virtual agent by name, then every concrete agent by name', function () {
    VirtualAgentModel::factory()->create(['name' => 'Writer', 'slug' => 'writer']);
    VirtualAgentModel::factory()->create(['name' => 'Planner', 'slug' => 'planner']);
    VirtualAgentModel::factory()->create(['name' => 'Analyst', 'slug' => 'analyst']);
    app(AgentRegistry::class)->register('triage', EchoAgent::class);
    app(AgentRegistry::class)->register('echo', EchoAgent::class);
    app(AgentRegistry::class)->register('summarizer', EchoAgent::class);

    expect(listRunnableAgents())->toBe([
        ['type' => 'virtual_agent', 'key' => 'analyst', 'name' => 'Analyst'],
        ['type' => 'virtual_agent', 'key' => 'planner', 'name' => 'Planner'],
        ['type' => 'virtual_agent', 'key' => 'writer', 'name' => 'Writer'],
        ['type' => 'concrete_agent', 'key' => 'echo', 'name' => 'echo'],
        ['type' => 'concrete_agent', 'key' => 'summarizer', 'name' => 'summarizer'],
        ['type' => 'concrete_agent', 'key' => 'triage', 'name' => 'triage'],
    ]);
});

it('lists nothing when there are no agents', function () {
    expect(listRunnableAgents())->toBe([]);
});

it('keeps only the agents the caller may run', function () {
    Gate::policy(VirtualAgentModel::class, RunnableHidingVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, RunnableHidingConcreteAgentOverridePolicy::class);

    VirtualAgentModel::factory()->create(['name' => 'Open', 'slug' => 'open']);
    VirtualAgentModel::factory()->create(['name' => 'Locked', 'slug' => 'locked']);
    app(AgentRegistry::class)->register('plain', EchoAgent::class);
    app(AgentRegistry::class)->register('saved', EchoAgent::class);
    ConcreteAgentOverrideModel::factory()->create(['agent' => 'saved']);

    expect(listRunnableAgents(new GenericUser(['id' => 1])))->toBe([
        ['type' => 'virtual_agent', 'key' => 'open', 'name' => 'Open'],
        ['type' => 'concrete_agent', 'key' => 'plain', 'name' => 'plain'],
    ]);
});

it('checks concrete agents against their overrides in one query', function () {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
    app(AgentRegistry::class)->register('echo-agent-two', EchoAgent::class);
    app(AgentRegistry::class)->register('echo-agent-three', EchoAgent::class);
    ConcreteAgentOverrideModel::factory()->create(['agent' => 'echo-agent']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        $agents = listRunnableAgents();
    } finally {
        DB::disableQueryLog();
    }

    $overrideQueries = array_filter(
        DB::getQueryLog(),
        fn (array $query): bool => str_contains((string) $query['query'], Tables::name('concrete_agent_overrides')),
    );

    expect(array_column($agents, 'key'))->toBe(['echo-agent', 'echo-agent-three', 'echo-agent-two'])
        ->and($overrideQueries)->toHaveCount(1);
});

it('dispatches the listing and listed events', function () {
    Event::fake([RunnableAgentsListingActionEvent::class, RunnableAgentsListedActionEvent::class]);
    VirtualAgentModel::factory()->create(['name' => 'Helper', 'slug' => 'helper']);
    $user = new GenericUser(['id' => 1]);

    $agents = listRunnableAgents($user);

    Event::assertDispatched(RunnableAgentsListingActionEvent::class, fn (RunnableAgentsListingActionEvent $event): bool => $event->user === $user);
    Event::assertDispatched(RunnableAgentsListedActionEvent::class, fn (RunnableAgentsListedActionEvent $event): bool => $event->agents === $agents);
});
