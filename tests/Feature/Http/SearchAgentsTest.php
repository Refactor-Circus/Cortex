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

beforeEach(function (): void {
    VirtualAgentModel::factory()->create(['name' => 'Helper', 'slug' => 'helper']);
    app(AgentRegistry::class)->register('helper-agent', EchoAgent::class);
});

it('answers virtual then concrete agents as agent references', function () {
    $this->getJson(route('cortex.search.index', ['q' => 'help']))
        ->assertOk()
        ->assertExactJson(['data' => [
            ['type' => 'virtual_agent', 'key' => 'helper', 'name' => 'Helper'],
            ['type' => 'concrete_agent', 'key' => 'helper-agent', 'name' => 'helper-agent'],
        ]]);
});

it('answers an empty list when nothing matches', function () {
    $this->getJson(route('cortex.search.index', ['q' => 'nothing']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('requires q as a string of at most 255 characters', function () {
    $this->getJson(route('cortex.search.index'))->assertUnprocessable()->assertJsonValidationErrors(['q']);
    $this->getJson(route('cortex.search.index', ['q' => str_repeat('a', 256)]))->assertUnprocessable()->assertJsonValidationErrors(['q']);
    $this->getJson(route('cortex.search.index', ['q' => str_repeat('a', 255)]))->assertOk();
});

it('refuses callers who may list neither kind of agent', function () {
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);

    $this->getJson(route('cortex.search.index', ['q' => 'help']))->assertUnauthorized();

    $this->actingAs(new GenericUser(['id' => 1, 'abilities' => ['virtual-agents.view', 'concrete-agents.view']]));

    $this->getJson(route('cortex.search.index', ['q' => 'help']))->assertForbidden();
});

it('searches only the kind the caller may list', function (array $abilities, array $expected) {
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);

    $this->actingAs(new GenericUser(['id' => 1, 'abilities' => [...$abilities, 'virtual-agents.view', 'concrete-agents.view']]));

    $this->getJson(route('cortex.search.index', ['q' => 'help']))
        ->assertOk()
        ->assertExactJson(['data' => $expected]);
})->with([
    'virtual agents only' => [['virtual-agents.viewAny'], [['type' => 'virtual_agent', 'key' => 'helper', 'name' => 'Helper']]],
    'concrete agents only' => [['concrete-agents.viewAny'], [['type' => 'concrete_agent', 'key' => 'helper-agent', 'name' => 'helper-agent']]],
]);
