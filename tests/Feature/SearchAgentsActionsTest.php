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
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\SearchAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\SearchAgentsData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\AgentsSearchedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\AgentsSearchingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;

/**
 * Lets the search list both kinds, but hides each virtual agent whose slug
 * starts with "secret".
 */
final class SearchHidingVirtualAgentPolicy extends VirtualAgentPolicy
{
    public function view(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return ! str_starts_with((string) $agent->slug, 'secret');
    }
}

/**
 * Hides each concrete agent that has a saved override, so a test can tell
 * the saved override from the unsaved stand-in the search checks.
 */
final class SearchHidingConcreteAgentOverridePolicy extends ConcreteAgentOverridePolicy
{
    public function view(?Authenticatable $user, ConcreteAgentOverrideModel $override): bool
    {
        return ! $override->exists;
    }
}

/**
 * @return list<array{type: string, key: string, name: string}>
 */
function searchAgents(string $query, ?Authenticatable $user = null): array
{
    return app(SearchAgentsAction::class)->execute(new SearchAgentsData(query: $query), $user);
}

it('finds virtual agents by name and by slug', function () {
    VirtualAgentModel::factory()->create(['name' => 'Research Helper', 'slug' => 'researcher']);
    VirtualAgentModel::factory()->create(['name' => 'Writer', 'slug' => 'copy-helper']);
    VirtualAgentModel::factory()->create(['name' => 'Planner', 'slug' => 'planner']);

    expect(searchAgents('helper'))->toBe([
        ['type' => 'virtual_agent', 'key' => 'researcher', 'name' => 'Research Helper'],
        ['type' => 'virtual_agent', 'key' => 'copy-helper', 'name' => 'Writer'],
    ]);
});

it('matches LIKE wildcards in the query literally', function () {
    VirtualAgentModel::factory()->create(['name' => '100% Sure', 'slug' => 'sure']);
    VirtualAgentModel::factory()->create(['name' => 'Under_Score', 'slug' => 'under-score']);
    VirtualAgentModel::factory()->create(['name' => 'Plain', 'slug' => 'plain']);
    VirtualAgentModel::factory()->create(['name' => 'Bang!', 'slug' => 'bang']);

    expect(array_column(searchAgents('%'), 'key'))->toBe(['sure'])
        ->and(array_column(searchAgents('_'), 'key'))->toBe(['under-score'])
        ->and(array_column(searchAgents('!'), 'key'))->toBe(['bang']);
});

it('returns up to five of each kind, virtual agents first, each kind ordered by name', function () {
    foreach (['G', 'C', 'A', 'F', 'B', 'E', 'D'] as $letter) {
        VirtualAgentModel::factory()->create(['name' => 'Helper '.$letter, 'slug' => 'helper-'.strtolower($letter)]);
        app(AgentRegistry::class)->register('helper-agent-'.strtolower($letter), EchoAgent::class);
    }

    expect(searchAgents('helper'))->toBe([
        ['type' => 'virtual_agent', 'key' => 'helper-a', 'name' => 'Helper A'],
        ['type' => 'virtual_agent', 'key' => 'helper-b', 'name' => 'Helper B'],
        ['type' => 'virtual_agent', 'key' => 'helper-c', 'name' => 'Helper C'],
        ['type' => 'virtual_agent', 'key' => 'helper-d', 'name' => 'Helper D'],
        ['type' => 'virtual_agent', 'key' => 'helper-e', 'name' => 'Helper E'],
        ['type' => 'concrete_agent', 'key' => 'helper-agent-a', 'name' => 'helper-agent-a'],
        ['type' => 'concrete_agent', 'key' => 'helper-agent-b', 'name' => 'helper-agent-b'],
        ['type' => 'concrete_agent', 'key' => 'helper-agent-c', 'name' => 'helper-agent-c'],
        ['type' => 'concrete_agent', 'key' => 'helper-agent-d', 'name' => 'helper-agent-d'],
        ['type' => 'concrete_agent', 'key' => 'helper-agent-e', 'name' => 'helper-agent-e'],
    ]);
});

it('matches concrete agent names case-insensitively', function () {
    app(AgentRegistry::class)->register('Echo-Agent', EchoAgent::class);
    app(AgentRegistry::class)->register('other', EchoAgent::class);

    expect(searchAgents('eCHO'))->toBe([
        ['type' => 'concrete_agent', 'key' => 'Echo-Agent', 'name' => 'Echo-Agent'],
    ]);
});

it('keeps only the results the caller may view', function () {
    Gate::policy(VirtualAgentModel::class, SearchHidingVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, SearchHidingConcreteAgentOverridePolicy::class);

    VirtualAgentModel::factory()->create(['name' => 'Open Helper', 'slug' => 'open-helper']);
    VirtualAgentModel::factory()->create(['name' => 'Secret Helper', 'slug' => 'secret-helper']);
    app(AgentRegistry::class)->register('helper-plain', EchoAgent::class);
    app(AgentRegistry::class)->register('helper-saved', EchoAgent::class);
    ConcreteAgentOverrideModel::factory()->create(['agent' => 'helper-saved']);

    expect(searchAgents('helper', new GenericUser(['id' => 1])))->toBe([
        ['type' => 'virtual_agent', 'key' => 'open-helper', 'name' => 'Open Helper'],
        ['type' => 'concrete_agent', 'key' => 'helper-plain', 'name' => 'helper-plain'],
    ]);
});

it('checks concrete agent results against their overrides in one query', function () {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
    app(AgentRegistry::class)->register('echo-agent-two', EchoAgent::class);
    ConcreteAgentOverrideModel::factory()->create(['agent' => 'echo-agent']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        $results = searchAgents('echo');
    } finally {
        DB::disableQueryLog();
    }

    $overrideQueries = array_filter(
        DB::getQueryLog(),
        fn (array $query): bool => str_contains((string) $query['query'], Tables::name('concrete_agent_overrides')),
    );

    expect(array_column($results, 'key'))->toBe(['echo-agent', 'echo-agent-two'])
        ->and($overrideQueries)->toHaveCount(1);
});

it('dispatches the searching and searched events', function () {
    Event::fake([AgentsSearchingActionEvent::class, AgentsSearchedActionEvent::class]);
    VirtualAgentModel::factory()->create(['name' => 'Helper', 'slug' => 'helper']);
    $user = new GenericUser(['id' => 1]);

    $results = app(SearchAgentsAction::class)->execute(new SearchAgentsData(query: 'help'), $user);

    Event::assertDispatched(AgentsSearchingActionEvent::class, fn (AgentsSearchingActionEvent $event): bool => $event->data->query === 'help' && $event->user === $user);
    Event::assertDispatched(AgentsSearchedActionEvent::class, fn (AgentsSearchedActionEvent $event): bool => $event->results === $results);
});

it('builds its data from the validated q input', function () {
    expect(SearchAgentsData::fromValidated(['q' => 'help'])->query)->toBe('help')
        ->and(SearchAgentsData::rules())->toHaveKey('q');
});
