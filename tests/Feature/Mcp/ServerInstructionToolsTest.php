<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\CreateServerInstructionVersionTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\DeleteServerInstructionsTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\ListServerInstructionVersionsTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\ListServersTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\PublishServerInstructionVersionTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\ShowServerInstructionsTool;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Services\McpServerRegistry;
use JayI\Cortex\Tests\Fixtures\EchoServer;

beforeEach(function () {
    app(McpServerRegistry::class)->register('echo', EchoServer::class);
});

it('lists servers in a data envelope with parity to the http payload', function () {
    $http = $this->getJson(route('cortex.servers.index'))->json('data');

    mcpTool(ListServersTool::class)
        ->assertOk()
        ->assertStructuredContent(['data' => $http]);
});

it('creates an instruction version with parity to the http payload', function () {
    mcpTool(CreateServerInstructionVersionTool::class, [
        'server' => 'echo',
        'content' => 'v1 instructions',
        'publish' => true,
    ])->assertOk();

    $http = $this->getJson(route('cortex.servers.instructions.versions.index', ['server' => 'echo']))->json('data.0');

    mcpTool(ShowServerInstructionsTool::class, ['server' => 'echo'])
        ->assertOk()
        ->assertStructuredContent(
            fn (AssertableJson $json) => $json
                ->where('server', 'echo')
                ->where('published_version', $http['version'])
                ->where('published_content', $http['content'])
                ->etc(),
        );
});

it('shows instructions with parity to the http payload', function () {
    mcpTool(CreateServerInstructionVersionTool::class, [
        'server' => 'echo',
        'content' => 'published',
        'publish' => true,
    ])->assertOk();

    $http = $this->getJson(route('cortex.servers.instructions.show', ['server' => 'echo']))->json('data');

    mcpTool(ShowServerInstructionsTool::class, ['server' => 'echo'])
        ->assertOk()
        ->assertStructuredContent($http);
});

it('lists instruction versions newest first with parity to the http payload', function () {
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'echo', 'content' => 'v1'])->assertOk();
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'echo', 'content' => 'v2'])->assertOk();

    $http = $this->getJson(route('cortex.servers.instructions.versions.index', ['server' => 'echo']))->json('data');

    mcpTool(ListServerInstructionVersionsTool::class, ['server' => 'echo'])
        ->assertOk()
        ->assertStructuredContent(['data' => $http]);
});

it('lists zero instruction versions without erroring', function () {
    McpInstructionModel::query()->create(['server' => 'echo']);

    mcpTool(ListServerInstructionVersionsTool::class, ['server' => 'echo'])
        ->assertOk()
        ->assertStructuredContent(['data' => []]);
});

it('publishes an instruction version with parity to the http payload', function () {
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'echo', 'content' => 'v1'])->assertOk();
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'echo', 'content' => 'v2'])->assertOk();

    $mcp = mcpTool(PublishServerInstructionVersionTool::class, ['server' => 'echo', 'version' => 1])
        ->assertOk();

    $http = $this->getJson(route('cortex.servers.instructions.show', ['server' => 'echo']))->json('data');

    $mcp->assertStructuredContent($http);

    expect($http['published_version'])->toBe(1);
});

it('deletes an instruction override', function () {
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'echo', 'content' => 'v1', 'publish' => true])
        ->assertOk();

    mcpTool(DeleteServerInstructionsTool::class, ['server' => 'echo'])
        ->assertOk()
        ->assertSee('Server instructions override deleted.');

    expect(McpInstructionModel::query()->count())->toBe(0);
});

it('errors not found for unregistered servers', function () {
    mcpTool(ShowServerInstructionsTool::class, ['server' => 'missing'])
        ->assertHasErrors(['Not found.']);

    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'missing', 'content' => 'x'])
        ->assertHasErrors(['Not found.']);
});

it('errors not found when no override exists yet', function () {
    mcpTool(ShowServerInstructionsTool::class, ['server' => 'echo'])
        ->assertHasErrors(['Not found.']);

    mcpTool(DeleteServerInstructionsTool::class, ['server' => 'echo'])
        ->assertHasErrors(['Not found.']);
});

it('validates create instruction version input', function () {
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'echo'])
        ->assertHasErrors();
});
