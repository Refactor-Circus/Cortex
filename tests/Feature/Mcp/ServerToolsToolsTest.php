<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\ClearServerToolsTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\ListServersTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\ShowServerInstructionsTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\UpdateServerToolsTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Tests\Fixtures\CollidingCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\NoUpdateToolsMcpInstructionPolicy;
use RefactorCircus\Cortex\Tests\Fixtures\ThrowingCortexTool;
use RefactorCircus\Keystone\Testing\Parity;

/*
 * The MCP surface of the tool override (AC 14, 15): update-server-tools-tool
 * and clear-server-tools-tool reach the same actions as the HTTP routes, store
 * the same state and answer the same payload.
 *
 * Code tools of `grouped`: echo-mcp-tool, echo-request-tool, renamed-echo.
 */
beforeEach(function (): void {
    Cache::store('array')->flush();

    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
    app(ToolRegistry::class)->register('echo-cortex-tool', EchoCortexTool::class);
});

/**
 * The grouped server's stored row as the parity tests compare it: keys are
 * generated per row, so versions are compared by number.
 *
 * @return array{tools: mixed, published_version: int|null, versions: list<int>}|null
 */
function mcpParityServerRow(): ?array
{
    $instruction = McpInstructionModel::query()->where('server', 'grouped')->first();

    return $instruction === null ? null : [
        'tools' => $instruction->tools,
        'published_version' => $instruction->publishedVersion?->version,
        'versions' => $instruction->versions()->pluck('version')->map(fn (mixed $version): int => (int) $version)->values()->all(),
    ];
}

it('lists the tools as update-server-tools-tool and clear-server-tools-tool on the cortex server', function (): void {
    expect(CortexServer::TOOLS)->toContain(UpdateServerToolsTool::class, ClearServerToolsTool::class)
        ->and(app(UpdateServerToolsTool::class)->name())->toBe('update-server-tools-tool')
        ->and(app(ClearServerToolsTool::class)->name())->toBe('clear-server-tools-tool');
});

describe('update-server-tools-tool', function (): void {
    it('stores the same state and answers the same payload as the HTTP route', function (?array $tools): void {
        app(ToolRegistry::class)->register('throwing-cortex-tool', ThrowingCortexTool::class);
        $this->freezeTime();

        $http = $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => $tools])->assertOk()->json('data');
        $httpRow = mcpParityServerRow();

        McpInstructionModel::query()->delete();
        app()->forgetScopedInstances();

        mcpTool(UpdateServerToolsTool::class, ['server' => 'grouped', 'tools' => $tools])
            ->assertOk()
            ->assertStructuredContent(['data' => $http]);

        expect(mcpParityServerRow())->toBe($httpRow);
    })->with([
        'valid entries' => [['echo-cortex-tool', 'renamed-echo']],
        'an entry whose tool throws' => [['throwing-cortex-tool', 'echo-mcp-tool']],
        'an empty list' => [[]],
        'null' => [null],
    ]);

    it('answers the show payload, ambiguous entries included', function (): void {
        mcpTool(UpdateServerToolsTool::class, ['server' => 'grouped', 'tools' => ['echo-mcp-tool']])->assertOk();

        app(ToolRegistry::class)->register('echo-mcp-tool', CollidingCortexTool::class);
        app()->forgetScopedInstances();

        $http = $this->getJson(route('cortex.servers.instructions.show', 'grouped'))->json('data');

        expect($http['ambiguous_tools'])->toBe(['echo-mcp-tool']);

        mcpTool(ShowServerInstructionsTool::class, ['server' => 'grouped'])
            ->assertOk()
            ->assertStructuredContent(['data' => $http]);
    });

    it('lists the tool fields with parity to the HTTP list', function (): void {
        mcpTool(UpdateServerToolsTool::class, ['server' => 'grouped', 'tools' => ['renamed-echo']])->assertOk();
        app()->forgetScopedInstances();

        $http = $this->getJson(route('cortex.servers.index'))->json('data');

        expect(collect($http)->firstWhere('name', 'grouped'))->toMatchArray(['tools' => ['renamed-echo'], 'tools_overridden' => true]);

        mcpTool(ListServersTool::class)
            ->assertOk()
            ->assertStructuredContent(['data' => $http]);
    });

    it('errors on a refused entry and on the cortex server', function (): void {
        mcpTool(UpdateServerToolsTool::class, ['server' => 'grouped', 'tools' => ['gone-tool']])->assertHasErrors();

        mcpTool(UpdateServerToolsTool::class, ['server' => 'cortex', 'tools' => []])
            ->assertHasErrors(['The tools of the [cortex] server cannot be overridden.']);

        expect(McpInstructionModel::query()->count())->toBe(0);
    });

    it('errors not found for an unknown server', function (): void {
        mcpTool(UpdateServerToolsTool::class, ['server' => 'missing', 'tools' => []])->assertHasErrors(['Not found.']);
    });
});

describe('clear-server-tools-tool', function (): void {
    it('stores the same state as the HTTP route', function (bool $withInstructions): void {
        $prepare = function () use ($withInstructions): void {
            McpInstructionModel::query()->delete();

            if ($withInstructions) {
                $this->postJson(route('cortex.servers.instructions.versions.store', 'grouped'), ['content' => 'Kept.', 'publish' => true])->assertCreated();
            }

            $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool']])->assertOk();
            app()->forgetScopedInstances();
        };

        $prepare();
        $this->deleteJson(route('cortex.servers.tools.destroy', 'grouped'))->assertNoContent();
        $httpRow = mcpParityServerRow();

        $prepare();
        mcpTool(ClearServerToolsTool::class, ['server' => 'grouped'])
            ->assertOk()
            ->assertSee('Server tool override cleared.');

        expect(mcpParityServerRow())->toBe($httpRow)
            ->and($httpRow === null)->toBe(! $withInstructions);
    })->with([
        'a tool-only row' => [false],
        'a row with instructions' => [true],
    ]);

    it('succeeds without a tool override', function (): void {
        mcpTool(ClearServerToolsTool::class, ['server' => 'grouped'])->assertOk();

        expect(McpInstructionModel::query()->count())->toBe(0);
    });

    it('errors on the cortex server and not found for an unknown server', function (): void {
        mcpTool(ClearServerToolsTool::class, ['server' => 'cortex'])
            ->assertHasErrors(['The tools of the [cortex] server cannot be overridden.']);

        mcpTool(ClearServerToolsTool::class, ['server' => 'missing'])->assertHasErrors(['Not found.']);
    });
});

it('refuses both tools under a policy without updateTools, keeping the stored list', function (): void {
    mcpTool(UpdateServerToolsTool::class, ['server' => 'grouped', 'tools' => ['echo-mcp-tool']])->assertOk();

    Gate::policy(McpInstructionModel::class, NoUpdateToolsMcpInstructionPolicy::class);

    mcpTool(UpdateServerToolsTool::class, ['server' => 'grouped', 'tools' => ['echo-cortex-tool']])->assertHasErrors(['Unauthorized.']);
    mcpTool(ClearServerToolsTool::class, ['server' => 'grouped'])->assertHasErrors(['Unauthorized.']);

    expect(McpInstructionModel::query()->where('server', 'grouped')->sole()->tools)->toBe(['echo-mcp-tool']);
});

it('offers every concrete server action on HTTP and MCP', function (): void {
    $domain = __DIR__.'/../../../src/Domains/ConcreteServer';

    expect(Parity::gaps("{$domain}/Actions/*.php", [
        'http' => "{$domain}/Http/Requests/*.php",
        'mcp' => "{$domain}/Mcp/Requests/*.php",
    ]))->toBe([]);
});
