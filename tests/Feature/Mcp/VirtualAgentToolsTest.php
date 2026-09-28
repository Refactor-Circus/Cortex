<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Mcp\Tools\CreateVirtualAgentTool;
use JayI\Cortex\Mcp\Tools\DeleteVirtualAgentTool;
use JayI\Cortex\Mcp\Tools\ListToolsTool;
use JayI\Cortex\Mcp\Tools\ListVirtualAgentsTool;
use JayI\Cortex\Mcp\Tools\ShowVirtualAgentTool;
use JayI\Cortex\Mcp\Tools\UpdateVirtualAgentTool;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Cortex\Tools\ToolRegistry;

it('creates a virtual agent with parity to the http payload', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
    VirtualAgent::factory()->create(['slug' => 'researcher']);

    $mcp = mcpTool(CreateVirtualAgentTool::class, [
        'name' => 'Coordinator',
        'slug' => 'coordinator',
        'instructions' => 'Coordinate.',
        'settings' => ['temperature' => 0.3],
        'tools' => ['echo'],
        'sub_agents' => ['researcher'],
        'concrete_sub_agents' => ['echo-agent'],
    ])->assertOk();

    $http = $this->getJson(route('cortex.virtual-agents.show', 'coordinator'))->json('data');

    $mcp->assertStructuredContent($http);

    expect($http['tools'])->toBe(['echo'])
        ->and($http['instructions'])->toBe('Coordinate.')
        ->and($http['published_version'])->toBe(1)
        ->and($http['sub_agents'])->toBe(['researcher'])
        ->and($http['concrete_sub_agents'])->toBe(['echo-agent']);
});

it('validates create input against the registries', function () {
    mcpTool(CreateVirtualAgentTool::class, [
        'name' => 'Broken',
        'slug' => 'broken',
        'instructions' => 'Nope.',
        'tools' => ['missing'],
    ])->assertHasErrors();
});

it('lists virtual agents in a data envelope', function () {
    VirtualAgent::factory()->create(['slug' => 'helper']);

    mcpTool(ListVirtualAgentsTool::class)
        ->assertOk()
        ->assertStructuredContent(
            fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->where('data.0.slug', 'helper')
                ->etc(),
        );
});

it('shows a virtual agent by slug', function () {
    VirtualAgent::factory()->create(['slug' => 'helper']);

    mcpTool(ShowVirtualAgentTool::class, ['slug' => 'helper'])
        ->assertOk()
        ->assertSee('helper');
});

it('errors not found for unknown slugs', function () {
    mcpTool(ShowVirtualAgentTool::class, ['slug' => 'missing'])
        ->assertHasErrors(['Not found.']);
});

it('updates a virtual agent with sync semantics and versions changed instructions', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    $agent = VirtualAgent::factory()->published('v1')->create(['slug' => 'helper', 'tools' => ['old']]);
    $agent->subAgents()->attach(VirtualAgent::factory()->create());

    mcpTool(UpdateVirtualAgentTool::class, [
        'slug' => 'helper',
        'instructions' => 'v2',
        'tools' => ['echo'],
        'sub_agents' => [],
    ])->assertOk();

    expect($agent->refresh()->tools)->toBe(['echo'])
        ->and($agent->subAgents)->toHaveCount(0)
        ->and($agent->publishedVersion?->content)->toBe('v2');
});

it('rejects circular sub-agent updates', function () {
    $a = VirtualAgent::factory()->create(['slug' => 'agent-a']);
    $b = VirtualAgent::factory()->create(['slug' => 'agent-b']);
    $a->subAgents()->attach($b);

    mcpTool(UpdateVirtualAgentTool::class, [
        'slug' => 'agent-b',
        'sub_agents' => ['agent-a'],
    ])->assertHasErrors();
});

it('deletes a virtual agent', function () {
    VirtualAgent::factory()->create(['slug' => 'helper']);

    mcpTool(DeleteVirtualAgentTool::class, ['slug' => 'helper'])
        ->assertOk()
        ->assertSee('Virtual agent deleted.');

    expect(VirtualAgent::query()->count())->toBe(0);
});

it('lists registered tools with parity to the http payload', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);

    $http = $this->getJson(route('cortex.tools.index'))->json('data');

    mcpTool(ListToolsTool::class)
        ->assertOk()
        ->assertStructuredContent(['data' => $http]);
});
