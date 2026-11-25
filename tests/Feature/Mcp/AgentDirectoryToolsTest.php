<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ListRunnableAgentsTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\SearchAgentsTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowSettingsSummaryTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\GrantedConcreteAgentOverridePolicy;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\GrantedVirtualAgentPolicy;

it('lists the three tools on the server', function () {
    expect(CortexServer::TOOLS)
        ->toContain(SearchAgentsTool::class)
        ->toContain(ListRunnableAgentsTool::class)
        ->toContain(ShowSettingsSummaryTool::class)
        ->and(app(SearchAgentsTool::class)->name())->toBe('search-agents-tool')
        ->and(app(ListRunnableAgentsTool::class)->name())->toBe('list-runnable-agents-tool')
        ->and(app(ShowSettingsSummaryTool::class)->name())->toBe('show-settings-summary-tool');
});

it('searches agents with parity to the http payload', function () {
    VirtualAgentModel::factory()->create(['name' => 'Helper', 'slug' => 'helper']);
    VirtualAgentModel::factory()->create(['name' => 'Other', 'slug' => 'other']);
    app(AgentRegistry::class)->register('helper-agent', EchoAgent::class);

    $http = $this->getJson(route('cortex.search.index', ['q' => 'help']))->assertOk()->json('data');

    expect($http)->toHaveCount(2);

    mcpTool(SearchAgentsTool::class, ['q' => 'help'])
        ->assertOk()
        ->assertStructuredContent(['data' => $http]);
});

it('answers an empty search in a data envelope', function () {
    $http = $this->getJson(route('cortex.search.index', ['q' => 'nothing']))->assertOk()->json('data');

    expect($http)->toBe([]);

    mcpTool(SearchAgentsTool::class, ['q' => 'nothing'])
        ->assertOk()
        ->assertStructuredContent(['data' => []]);
});

it('refuses a search without a query or with one over 255 characters', function () {
    mcpTool(SearchAgentsTool::class)->assertHasErrors();
    mcpTool(SearchAgentsTool::class, ['q' => str_repeat('a', 256)])->assertHasErrors();
});

it('lists runnable agents with parity to the http payload', function () {
    VirtualAgentModel::factory()->create(['name' => 'Helper', 'slug' => 'helper']);
    VirtualAgentModel::factory()->create(['name' => 'Assistant', 'slug' => 'assistant']);
    app(AgentRegistry::class)->register('triage', EchoAgent::class);
    app(AgentRegistry::class)->register('echo', EchoAgent::class);

    $http = $this->getJson(route('cortex.runnable-agents.index'))->assertOk()->json('data');

    expect($http)->not->toBe([]);

    mcpTool(ListRunnableAgentsTool::class)
        ->assertOk()
        ->assertStructuredContent(['data' => $http]);
});

it('answers an empty runnable agents list in a data envelope', function () {
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);
    VirtualAgentModel::factory()->create(['name' => 'Helper', 'slug' => 'helper']);
    app(AgentRegistry::class)->register('helper-agent', EchoAgent::class);

    $this->actingAs(new GenericUser(['id' => 1, 'abilities' => ['virtual-agents.viewAny', 'concrete-agents.viewAny']]));

    $http = $this->getJson(route('cortex.runnable-agents.index'))->assertOk()->json('data');

    expect($http)->toBe([]);

    mcpTool(ListRunnableAgentsTool::class)
        ->assertOk()
        ->assertStructuredContent(['data' => []]);
});

it('keeps only the agents the caller may run, as http does', function () {
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);
    VirtualAgentModel::factory()->create(['name' => 'Helper', 'slug' => 'helper']);
    app(AgentRegistry::class)->register('helper-agent', EchoAgent::class);

    $this->actingAs(new GenericUser(['id' => 1, 'abilities' => ['virtual-agents.viewAny', 'concrete-agents.run']]));

    $http = $this->getJson(route('cortex.runnable-agents.index'))->assertOk()->json('data');

    expect($http)->toBe([['type' => 'concrete_agent', 'key' => 'helper-agent', 'name' => 'helper-agent']]);

    mcpTool(ListRunnableAgentsTool::class)
        ->assertOk()
        ->assertStructuredContent(['data' => $http]);
});

it('refuses callers who may list neither kind of agent', function (?array $abilities) {
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);

    if ($abilities !== null) {
        $this->actingAs(new GenericUser(['id' => 1, 'abilities' => $abilities]));
    }

    mcpTool(SearchAgentsTool::class, ['q' => 'help'])->assertHasErrors(['Unauthorized.']);
    mcpTool(ListRunnableAgentsTool::class)->assertHasErrors(['Unauthorized.']);
})->with([
    'guest' => [null],
    'signed-in user' => [['virtual-agents.view', 'virtual-agents.run', 'concrete-agents.view', 'concrete-agents.run']],
]);

it('shows the settings summary with parity to the http payload', function () {
    config()->set('cortex.providers', ['anthropic' => ['claude-sonnet-5-5', 'claude-opus-5-5']]);
    config()->set('cortex.cache.enabled', false);
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    app(AgentRegistry::class)->register('echo', EchoAgent::class);
    app(McpServerRegistry::class)->register('echo', EchoServer::class);

    $http = $this->getJson(route('cortex.settings-summary.show'))->assertOk()->json('data');

    mcpTool(ShowSettingsSummaryTool::class)
        ->assertOk()
        ->assertStructuredContent(['data' => $http]);
});

it('shows the settings summary with no providers configured', function () {
    config()->set('cortex.providers', []);

    $http = $this->getJson(route('cortex.settings-summary.show'))->assertOk()->json('data');

    expect($http['providers'])->toBe([]);

    mcpTool(ShowSettingsSummaryTool::class)
        ->assertOk()
        ->assertStructuredContent(['data' => $http]);
});
