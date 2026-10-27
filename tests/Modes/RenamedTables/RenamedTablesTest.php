<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoMcpTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Support\ServerContextTools;

it('migrates every table under its configured name', function (): void {
    foreach (array_keys(Tables::DEFAULTS) as $key) {
        expect(Schema::hasTable('crm_'.$key))->toBeTrue()
            ->and(Schema::hasTable('cortex_'.$key))->toBeFalse();
    }
});

it('manages virtual agents, their versions and sub-agents on renamed tables', function (): void {
    VirtualAgentModel::factory()->create(['slug' => 'researcher']);

    $this->postJson(route('cortex.virtual-agents.store'), [
        'name' => 'Coordinator',
        'slug' => 'coordinator',
        'instructions' => 'v1',
        'sub_agents' => ['researcher'],
    ])->assertCreated()->assertJsonPath('data.sub_agents.0', 'researcher');

    // Validation rules look in the renamed tables too.
    $this->postJson(route('cortex.virtual-agents.store'), [
        'name' => 'Again',
        'slug' => 'coordinator',
        'instructions' => 'v1',
        'sub_agents' => ['missing'],
    ])->assertJsonValidationErrors(['slug', 'sub_agents.0']);

    // The cycle check walks the renamed pivot.
    $this->patchJson(route('cortex.virtual-agents.update', 'researcher'), ['sub_agents' => ['coordinator']])
        ->assertJsonValidationErrors(['sub_agents']);

    $this->postJson(route('cortex.virtual-agents.versions.store', 'coordinator'), ['content' => 'v2', 'publish' => true])
        ->assertCreated()
        ->assertJsonPath('data.version', 2);
    $this->getJson(route('cortex.virtual-agents.versions.index', 'coordinator'))->assertOk()->assertJsonCount(2, 'data');

    $this->deleteJson(route('cortex.virtual-agents.destroy', 'coordinator'))->assertNoContent();

    expect(VirtualAgentModel::query()->pluck('slug')->all())->toBe(['researcher'])
        ->and(VirtualAgentModel::query()->sole()->getTable())->toBe('crm_virtual_agents');
});

it('versions tool descriptions, server instructions and concrete agent overrides on renamed tables', function (): void {
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    app(McpServerRegistry::class)->register('echo', EchoServer::class);
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);

    $this->postJson(route('cortex.tools.description.versions.store', ['tool' => 'echo']), ['content' => 'Described.', 'publish' => true])->assertCreated();
    $this->postJson(route('cortex.servers.instructions.versions.store', ['server' => 'echo']), ['content' => 'Instructed.', 'publish' => true])->assertCreated();
    $this->postJson(route('cortex.concrete-agents.versions.store', 'echo-agent'), ['content' => 'Overridden.', 'publish' => true])->assertCreated();

    $this->getJson(route('cortex.tools.description.show', ['tool' => 'echo']))->assertJsonPath('data.published_content', 'Described.');
    $this->getJson(route('cortex.servers.instructions.show', ['server' => 'echo']))->assertJsonPath('data.published_content', 'Instructed.');
    $this->getJson(route('cortex.concrete-agents.show', 'echo-agent'))->assertJsonPath('data.instructions', 'Overridden.');

    $this->deleteJson(route('cortex.tools.description.destroy', ['tool' => 'echo']))->assertNoContent();
    $this->deleteJson(route('cortex.servers.instructions.destroy', ['server' => 'echo']))->assertNoContent();
    $this->deleteJson(route('cortex.concrete-agents.override.destroy', 'echo-agent'))->assertNoContent();

    expect(ToolDescriptionModel::query()->count())->toBe(0)
        ->and(McpInstructionModel::query()->count())->toBe(0)
        ->and(ConcreteAgentOverrideModel::query()->count())->toBe(0);
});

it('saves, serves and restores a concrete server tool override on renamed tables', function (): void {
    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
    app(ToolRegistry::class)->register('echo-cortex-tool', EchoCortexTool::class);

    expect(Schema::hasColumn('crm_mcp_instructions', 'tools'))->toBeTrue();

    // Save: the override row lands in the renamed table, in stored order.
    $this->putJson(route('cortex.servers.tools.update', ['server' => 'grouped']), ['tools' => ['echo-cortex-tool', 'renamed-echo']])
        ->assertOk()
        ->assertJsonPath('data.tools', ['echo-cortex-tool', 'renamed-echo'])
        ->assertJsonPath('data.tools_overridden', true);

    $instruction = McpInstructionModel::query()->sole();

    expect($instruction->getTable())->toBe('crm_mcp_instructions')
        ->and($instruction->tools)->toBe(['echo-cortex-tool', 'renamed-echo'])
        ->and(DB::table('crm_mcp_instructions')->where('server', 'grouped')->count())->toBe(1);

    // Serve: a client of the server lists and calls exactly the stored tools.
    app()->forgetScopedInstances();

    expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-cortex-tool', 'renamed-echo']);

    GroupedToolsServer::tool(EchoCortexTool::class, ['message' => 'renamed tables'])->assertOk()->assertSee('renamed tables');
    GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'gone'])->assertHasErrors(['Tool [echo-mcp-tool] not found.']);

    // Restore: clearing a tool-only row removes it and serves the code tools again.
    $this->deleteJson(route('cortex.servers.tools.destroy', ['server' => 'grouped']))->assertNoContent();

    app()->forgetScopedInstances();

    expect(McpInstructionModel::query()->count())->toBe(0)
        ->and(DB::table('crm_mcp_instructions')->count())->toBe(0)
        ->and(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool', 'search_tools', 'execute_tools']);

    GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'code tool'])->assertOk()->assertSee('code tool');
});

it('keeps a tool override when the instructions are deleted on renamed tables', function (): void {
    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);

    $this->postJson(route('cortex.servers.instructions.versions.store', ['server' => 'grouped']), ['content' => 'Instructed.', 'publish' => true])->assertCreated();
    $this->putJson(route('cortex.servers.tools.update', ['server' => 'grouped']), ['tools' => ['renamed-echo']])->assertOk();

    $this->deleteJson(route('cortex.servers.instructions.destroy', ['server' => 'grouped']))
        ->assertOk()
        ->assertJsonPath('data.tools_overridden', true);

    expect(DB::table('crm_mcp_instruction_versions')->count())->toBe(0)
        ->and(McpInstructionModel::query()->sole()->published_version_id)->toBeNull()
        ->and(McpInstructionModel::query()->sole()->tools)->toBe(['renamed-echo']);

    app()->forgetScopedInstances();

    expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['renamed-echo']);
});

it('manages redirect domains on a renamed table', function (): void {
    $id = $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'claude.ai'])->assertCreated()->json('data.id');

    $this->getJson(route('cortex.redirect-domains.index'))->assertOk()->assertJsonCount(1, 'data');
    $this->deleteJson(route('cortex.redirect-domains.destroy', $id))->assertNoContent();

    expect(RedirectDomainModel::query()->count())->toBe(0);
});
