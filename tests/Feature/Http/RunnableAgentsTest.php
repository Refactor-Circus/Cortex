<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\GrantedConcreteAgentOverridePolicy;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\GrantedVirtualAgentPolicy;

it('answers every runnable agent, unpaginated, virtual then concrete', function () {
    foreach (range(1, 20) as $number) {
        VirtualAgentModel::factory()->create(['name' => sprintf('Agent %02d', $number), 'slug' => sprintf('agent-%02d', $number)]);
    }
    app(AgentRegistry::class)->register('triage', EchoAgent::class);
    app(AgentRegistry::class)->register('echo', EchoAgent::class);

    $expected = [
        ...array_map(fn (int $number): array => [
            'type' => 'virtual_agent',
            'key' => sprintf('agent-%02d', $number),
            'name' => sprintf('Agent %02d', $number),
        ], range(1, 20)),
        ['type' => 'concrete_agent', 'key' => 'echo', 'name' => 'echo'],
        ['type' => 'concrete_agent', 'key' => 'triage', 'name' => 'triage'],
    ];

    $this->getJson(route('cortex.runnable-agents.index'))
        ->assertOk()
        ->assertExactJson(['data' => $expected]);
});

it('answers an empty list when nothing is runnable', function () {
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);
    VirtualAgentModel::factory()->create(['name' => 'Helper', 'slug' => 'helper']);
    app(AgentRegistry::class)->register('helper-agent', EchoAgent::class);

    $this->actingAs(new GenericUser(['id' => 1, 'abilities' => ['virtual-agents.viewAny', 'concrete-agents.viewAny']]));

    $this->getJson(route('cortex.runnable-agents.index'))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('keeps only the agents the caller may run', function (array $abilities, array $expected) {
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);
    VirtualAgentModel::factory()->create(['name' => 'Helper', 'slug' => 'helper']);
    app(AgentRegistry::class)->register('helper-agent', EchoAgent::class);

    $this->actingAs(new GenericUser(['id' => 1, 'abilities' => ['virtual-agents.viewAny', ...$abilities]]));

    $this->getJson(route('cortex.runnable-agents.index'))
        ->assertOk()
        ->assertExactJson(['data' => $expected]);
})->with([
    'virtual agents only' => [['virtual-agents.run'], [['type' => 'virtual_agent', 'key' => 'helper', 'name' => 'Helper']]],
    'concrete agents only' => [['concrete-agents.run'], [['type' => 'concrete_agent', 'key' => 'helper-agent', 'name' => 'helper-agent']]],
]);

it('refuses callers who may list neither kind of agent', function () {
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);

    $this->getJson(route('cortex.runnable-agents.index'))->assertUnauthorized();

    $this->actingAs(new GenericUser(['id' => 1, 'abilities' => ['virtual-agents.run', 'concrete-agents.run']]));

    $this->getJson(route('cortex.runnable-agents.index'))->assertForbidden();
});

it('lets a caller who may list either kind in', function (string $ability) {
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);

    $this->actingAs(new GenericUser(['id' => 1, 'abilities' => [$ability]]));

    $this->getJson(route('cortex.runnable-agents.index'))
        ->assertOk()
        ->assertExactJson(['data' => []]);
})->with(['virtual-agents.viewAny', 'concrete-agents.viewAny']);
