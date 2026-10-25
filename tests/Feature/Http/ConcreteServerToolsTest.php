<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\CollidingCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\NoUpdateToolsMcpInstructionPolicy;
use RefactorCircus\Cortex\Tests\Fixtures\ThrowingCortexTool;

/*
 * The HTTP surface of the tool override (AC 14, 15). The rules themselves are
 * tested once at the action (ConcreteServerToolsActionsTest); this file checks
 * the routes by name, the 422/404 mapping, authorization and the payload.
 *
 * Code tools of `grouped`: echo-mcp-tool, echo-request-tool, renamed-echo.
 */
beforeEach(function (): void {
    Cache::store('array')->flush();

    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
    app(ToolRegistry::class)->register('echo-cortex-tool', EchoCortexTool::class);
});

/**
 * Write the grouped server's override row directly, for values the update
 * action refuses (stale entries, a corrupt value), then start a new request.
 */
function writeHttpServerTools(string $server, mixed $tools): void
{
    McpInstructionModel::query()->updateOrCreate(['server' => $server], ['tools' => null]);

    DB::table(Tables::name('mcp_instructions'))->where('server', $server)->update([
        'tools' => $tools === null ? null : json_encode($tools, JSON_THROW_ON_ERROR),
    ]);

    $cache = app(PublicationCache::class);
    $cache->forget($cache->mcpToolsKey(), $cache->mcpInstructionsKey());

    app()->forgetScopedInstances();
}

/**
 * The server's entry in the server list, read in a new request.
 *
 * @return array<string, mixed>
 */
function listedHttpServer(string $server): array
{
    app()->forgetScopedInstances();

    /** @var array<string, mixed> */
    return collect(test()->getJson(route('cortex.servers.index'))->assertOk()->json('data'))->firstWhere('name', $server);
}

/**
 * The server's instructions show payload, read in a new request.
 *
 * @return array<string, mixed>
 */
function shownHttpServer(string $server): array
{
    app()->forgetScopedInstances();

    /** @var array<string, mixed> */
    return test()->getJson(route('cortex.servers.instructions.show', $server))->assertOk()->json('data');
}

/**
 * @return list<mixed>|null
 */
function storedHttpServerTools(string $server): ?array
{
    $value = McpInstructionModel::query()->where('server', $server)->value('tools');

    return is_string($value) ? json_decode($value, true) : $value;
}

describe('PUT cortex.servers.tools.update', function (): void {
    it('stores the list and answers with the server resource', function (): void {
        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-cortex-tool', 'echo-mcp-tool']])
            ->assertOk()
            ->assertJsonPath('data.server', 'grouped')
            ->assertJsonPath('data.tools', ['echo-cortex-tool', 'echo-mcp-tool'])
            ->assertJsonPath('data.tools_overridden', true)
            ->assertJsonPath('data.missing_tools', [])
            ->assertJsonPath('data.ambiguous_tools', []);

        expect(McpInstructionModel::query()->where('server', 'grouped')->sole()->tools)->toBe(['echo-cortex-tool', 'echo-mcp-tool']);
    });

    it('answers 200 for a null list, which keeps the code tools', function (): void {
        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => null])
            ->assertOk()
            ->assertJsonPath('data.tools', ['echo-mcp-tool', 'echo-request-tool', 'renamed-echo'])
            ->assertJsonPath('data.tools_overridden', false);
    });

    it('maps a refused entry and a missing key to 422 on the field', function (): void {
        $route = route('cortex.servers.tools.update', 'grouped');

        $this->putJson($route, ['tools' => ['echo-mcp-tool', 'gone-tool']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tools.1'])
            ->assertJsonMissingValidationErrors(['tools.0']);

        $this->putJson($route, [])->assertUnprocessable()->assertJsonValidationErrors(['tools']);

        expect(McpInstructionModel::query()->count())->toBe(0);
    });

    it('refuses the cortex server with a 422', function (): void {
        $this->putJson(route('cortex.servers.tools.update', 'cortex'), ['tools' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['server']);

        expect(McpInstructionModel::query()->count())->toBe(0);
    });

    it('answers 404 for an unknown server', function (): void {
        $this->putJson(route('cortex.servers.tools.update', 'missing'), ['tools' => []])->assertNotFound();
    });
});

describe('DELETE cortex.servers.tools.destroy', function (): void {
    it('clears a tool-only override, removing the row', function (): void {
        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool']])->assertOk();

        $this->deleteJson(route('cortex.servers.tools.destroy', 'grouped'))->assertNoContent();

        expect(McpInstructionModel::query()->count())->toBe(0)
            ->and(listedHttpServer('grouped'))->toMatchArray([
                'tools' => ['echo-mcp-tool', 'echo-request-tool', 'renamed-echo'],
                'tools_overridden' => false,
            ]);
    });

    it('clears the tools and keeps the published instructions', function (): void {
        $this->postJson(route('cortex.servers.instructions.versions.store', 'grouped'), ['content' => 'Kept.', 'publish' => true])->assertCreated();
        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool']])->assertOk();

        $this->deleteJson(route('cortex.servers.tools.destroy', 'grouped'))->assertNoContent();

        expect(McpInstructionModel::query()->where('server', 'grouped')->sole()->tools)->toBeNull()
            ->and(shownHttpServer('grouped'))->toMatchArray([
                'published_version' => 1,
                'published_content' => 'Kept.',
                'tools_overridden' => false,
            ]);
    });

    it('answers 204 for a server without a tool override', function (): void {
        $this->deleteJson(route('cortex.servers.tools.destroy', 'grouped'))->assertNoContent();

        expect(McpInstructionModel::query()->count())->toBe(0);
    });

    it('refuses the cortex server with a 422 and answers 404 for an unknown server', function (): void {
        $this->deleteJson(route('cortex.servers.tools.destroy', 'cortex'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['server']);

        $this->deleteJson(route('cortex.servers.tools.destroy', 'missing'))->assertNotFound();
    });
});

describe('authorization', function (): void {
    it('refuses a guest with 401 and a signed-in user with 403 under a policy without updateTools, keeping the stored list', function (): void {
        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool']])->assertOk();

        Gate::policy(McpInstructionModel::class, NoUpdateToolsMcpInstructionPolicy::class);

        $update = route('cortex.servers.tools.update', 'grouped');
        $clear = route('cortex.servers.tools.destroy', 'grouped');

        $this->putJson($update, ['tools' => ['echo-cortex-tool']])->assertUnauthorized();
        $this->deleteJson($clear)->assertUnauthorized();

        $this->actingAs(new GenericUser(['id' => 1]));

        $this->putJson($update, ['tools' => ['echo-cortex-tool']])->assertForbidden();
        $this->deleteJson($clear)->assertForbidden();

        expect(McpInstructionModel::query()->where('server', 'grouped')->sole()->tools)->toBe(['echo-mcp-tool']);
    });

    it('checks the server without an override row against the policy too', function (): void {
        Gate::policy(McpInstructionModel::class, NoUpdateToolsMcpInstructionPolicy::class);

        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-cortex-tool']])->assertUnauthorized();

        expect(McpInstructionModel::query()->count())->toBe(0);
    });
});

describe('resource payload (AC 15)', function (): void {
    it('reports the code tools and no override for a server without a row', function (): void {
        expect(listedHttpServer('grouped'))->toMatchArray([
            'tools' => ['echo-mcp-tool', 'echo-request-tool', 'renamed-echo'],
            'tools_overridden' => false,
            'missing_tools' => [],
            'ambiguous_tools' => [],
        ]);
    });

    it('reports a valid override on the list and the show payload, in stored order', function (): void {
        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['renamed-echo', 'echo-cortex-tool', 'echo-mcp-tool']])->assertOk();

        $expected = [
            'tools' => ['renamed-echo', 'echo-cortex-tool', 'echo-mcp-tool'],
            'tools_overridden' => true,
            'missing_tools' => [],
            'ambiguous_tools' => [],
        ];

        expect(listedHttpServer('grouped'))->toMatchArray($expected)
            ->and(shownHttpServer('grouped'))->toMatchArray($expected);
    });

    it('reports an empty list as an override serving no tools', function (): void {
        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => []])
            ->assertOk()
            ->assertJsonPath('data.tools', [])
            ->assertJsonPath('data.tools_overridden', true);
    });

    it('reports a stale entry in missing_tools and leaves it out of tools', function (): void {
        writeHttpServerTools('grouped', ['gone-tool', 'echo-mcp-tool']);

        $expected = [
            'tools' => ['echo-mcp-tool'],
            'tools_overridden' => true,
            'missing_tools' => ['gone-tool'],
            'ambiguous_tools' => [],
        ];

        expect(listedHttpServer('grouped'))->toMatchArray($expected)
            ->and(shownHttpServer('grouped'))->toMatchArray($expected);
    });

    it('reports an entry whose tool throws while built in missing_tools', function (): void {
        app(ToolRegistry::class)->register('throwing-cortex-tool', ThrowingCortexTool::class);

        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['throwing-cortex-tool', 'echo-mcp-tool']])
            ->assertOk()
            ->assertJsonPath('data.tools', ['echo-mcp-tool'])
            ->assertJsonPath('data.missing_tools', ['throwing-cortex-tool']);

        expect(listedHttpServer('grouped'))->toMatchArray([
            'tools' => ['echo-mcp-tool'],
            'missing_tools' => ['throwing-cortex-tool'],
        ]);
    });

    it('reports an entry naming a code tool and a registered tool in ambiguous_tools, without logging', function (): void {
        $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool', 'renamed-echo']])->assertOk();

        // Registered after the save, under a code tool's name.
        app(ToolRegistry::class)->register('echo-mcp-tool', CollidingCortexTool::class);

        Log::spy();

        $expected = [
            'tools' => ['echo-mcp-tool', 'renamed-echo'],
            'tools_overridden' => true,
            'missing_tools' => [],
            'ambiguous_tools' => ['echo-mcp-tool'],
        ];

        expect(listedHttpServer('grouped'))->toMatchArray($expected)
            ->and(shownHttpServer('grouped'))->toMatchArray($expected);

        Log::shouldNotHaveReceived('warning');
    });

    it('reports a corrupt stored value as no override', function (): void {
        writeHttpServerTools('grouped', 'echo-mcp-tool');

        expect(shownHttpServer('grouped'))->toMatchArray([
            'tools' => ['echo-mcp-tool', 'echo-request-tool', 'renamed-echo'],
            'tools_overridden' => false,
        ]);
    });

    it('reports the cortex server as not overridden, with or without a stored tools row', function (): void {
        $codeTools = app(McpServerRegistry::class)->defaultTools('cortex');

        expect(listedHttpServer('cortex'))->toMatchArray(['tools' => $codeTools, 'tools_overridden' => false]);

        writeHttpServerTools('cortex', ['list-tools-tool']);

        expect(listedHttpServer('cortex'))->toMatchArray([
            'tools' => $codeTools,
            'tools_overridden' => false,
            'missing_tools' => [],
            'ambiguous_tools' => [],
        ])
            ->and(shownHttpServer('cortex'))->toMatchArray(['tools' => $codeTools, 'tools_overridden' => false]);
    });
});

it('keeps the tool override when the instructions are deleted, and the payload says so (AC 11)', function (): void {
    $this->postJson(route('cortex.servers.instructions.versions.store', 'grouped'), ['content' => 'Published.', 'publish' => true])->assertCreated();
    $this->putJson(route('cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool']])->assertOk();

    $this->deleteJson(route('cortex.servers.instructions.destroy', 'grouped'))->assertSuccessful();

    expect(storedHttpServerTools('grouped'))->toBe(['echo-mcp-tool'])
        ->and(shownHttpServer('grouped'))->toMatchArray([
            'published_version' => null,
            'tools' => ['echo-mcp-tool'],
            'tools_overridden' => true,
        ])
        ->and(listedHttpServer('grouped'))->toMatchArray([
            'instructions' => 'Grouped tools server instructions.',
            'tools_overridden' => true,
        ]);
});
