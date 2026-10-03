<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentVersionTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ListVirtualAgentVersionsTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\PublishVirtualAgentVersionTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentVersionTool;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

it('creates a version with parity to the http payload', function () {
    VirtualAgentModel::factory()->published('v1')->create(['slug' => 'helper']);

    $mcp = mcpTool(CreateVirtualAgentVersionTool::class, ['slug' => 'helper', 'content' => 'v2'])->assertOk();

    $http = $this->getJson(route('cortex.virtual-agents.versions.show', ['helper', 2]))->json('data');

    $mcp->assertStructuredContent($http);
});

it('lists versions in a data envelope', function () {
    VirtualAgentModel::factory()->published('v1')->create(['slug' => 'helper']);

    mcpTool(ListVirtualAgentVersionsTool::class, ['slug' => 'helper'])
        ->assertOk()
        ->assertStructuredContent(
            fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->where('data.0.version', 1)
                ->etc(),
        );
});

it('shows a version', function () {
    VirtualAgentModel::factory()->published('v1')->create(['slug' => 'helper']);

    mcpTool(ShowVirtualAgentVersionTool::class, ['slug' => 'helper', 'version' => 1])
        ->assertOk()
        ->assertSee('v1');
});

it('publishes a version', function () {
    $agent = VirtualAgentModel::factory()->published('v1')->create(['slug' => 'helper']);
    mcpTool(CreateVirtualAgentVersionTool::class, ['slug' => 'helper', 'content' => 'v2']);

    mcpTool(PublishVirtualAgentVersionTool::class, ['slug' => 'helper', 'version' => 2])
        ->assertOk()
        ->assertStructuredContent(
            fn (AssertableJson $json) => $json
                ->where('published_version', 2)
                ->where('instructions', 'v2')
                ->etc(),
        );

    expect($agent->refresh()->publishedVersion?->version)->toBe(2);
});

it('errors not found for unknown agents and versions', function () {
    VirtualAgentModel::factory()->published()->create(['slug' => 'helper']);

    mcpTool(ShowVirtualAgentVersionTool::class, ['slug' => 'missing', 'version' => 1])->assertHasErrors(['Not found.']);
    mcpTool(PublishVirtualAgentVersionTool::class, ['slug' => 'helper', 'version' => 9])->assertHasErrors(['Not found.']);
});
