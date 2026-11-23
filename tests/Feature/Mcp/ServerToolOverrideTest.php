<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Cortex\CortexServiceProvider;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Concerns\HasCortexOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Concerns\HasVersionedInstructions;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerContextMemo;
use RefactorCircus\Cortex\Domains\ConcreteServer\Support\Server;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Support\AiToolAsMcpTool;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Tests\Fixtures\ConstructorToolsServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoMcpTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoRequestTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\GatedAiTool;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Fixtures\HiddenMcpTool;
use RefactorCircus\Cortex\Tests\Fixtures\NamedMcpTool;
use RefactorCircus\Cortex\Tests\Support\ServerContextTools;

beforeEach(function (): void {
    Cache::store('array')->flush();
    GatedAiTool::reset();

    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
    app(McpServerRegistry::class)->register('constructor', ConstructorToolsServer::class);

    app(ToolRegistry::class)->register('echo-cortex-tool', EchoCortexTool::class);
});

/**
 * Save a server's tool override through the update action, then start a new
 * request.
 *
 * @param  list<string>|null  $tools
 */
function saveOverriddenTools(string $server, ?array $tools): void
{
    app(UpdateConcreteServerToolsAction::class)->execute($server, UpdateConcreteServerToolsData::from(['tools' => $tools], $server));

    app()->forgetScopedInstances();
}

/**
 * Write a server's override row directly, for lists the update action would
 * refuse (tools a server adds at runtime), then start a new request.
 *
 * @param  list<string>|null  $tools
 */
function writeOverriddenTools(string $server, ?array $tools, ?string $published = null): McpInstructionModel
{
    $instruction = McpInstructionModel::query()->updateOrCreate(['server' => $server], ['tools' => $tools]);

    if ($published !== null) {
        $version = $instruction->versions()->create(['version' => 1, 'content' => $published]);
        $instruction->published_version_id = $version->getKey();
        $instruction->save();
    }

    $cache = app(PublicationCache::class);
    $cache->forget($cache->mcpToolsKey(), $cache->mcpInstructionsKey());

    app()->forgetScopedInstances();

    return $instruction;
}

describe('the served list', function (): void {
    it('lists exactly the stored tools, code tools by name and registered tools from the registry', function (): void {
        saveOverriddenTools('grouped', ['renamed-echo', 'echo-cortex-tool']);

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['renamed-echo', 'echo-cortex-tool']);

        GroupedToolsServer::tool(NamedMcpTool::class, ['message' => 'code tool'])->assertOk()->assertSee('code tool');
        GroupedToolsServer::tool(EchoCortexTool::class, ['message' => 'registered tool'])->assertOk()->assertSee('registered tool');
    });

    it('lists the code tools without a row, with a null list and with an instructions-only row', function (?Closure $row): void {
        $row?->__invoke();

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool', 'search_tools', 'execute_tools']);

        GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'still here'])->assertOk()->assertSee('still here');
    })->with([
        'no row' => [null],
        'null list' => [fn () => fn () => writeOverriddenTools('grouped', null)],
        'null list saved through the action' => [fn () => fn () => saveOverriddenTools('grouped', null)],
        'instructions only' => [fn () => fn () => writeOverriddenTools('grouped', null, 'Published grouped instructions.')],
    ]);

    it('fails a call to a tool the override removed, naming the tool', function (): void {
        saveOverriddenTools('grouped', ['renamed-echo']);

        GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'gone'])
            ->assertHasErrors(['Tool [echo-mcp-tool] not found.']);
    });

    it('serves no tools for an empty list', function (): void {
        saveOverriddenTools('grouped', []);

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe([]);

        GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'gone'])
            ->assertHasErrors(['Tool [echo-mcp-tool] not found.']);
    });

    it('serves a tool-only row, which has no published instructions', function (): void {
        $instruction = writeOverriddenTools('grouped', ['echo-mcp-tool']);

        expect($instruction->fresh()?->published_version_id)->toBeNull()
            ->and($instruction->versions()->count())->toBe(0)
            ->and(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool'])
            ->and((new GroupedToolsServer(new FakeTransporter))->createContext()->instructions)->toBe('Grouped tools server instructions.');
    });

    it('serves the tool override together with the published instructions', function (): void {
        writeOverriddenTools('grouped', ['renamed-echo'], 'Published grouped instructions.');

        $context = (new GroupedToolsServer(new FakeTransporter))->createContext();

        expect($context->instructions)->toBe('Published grouped instructions.')
            ->and(ServerContextTools::names($context))->toBe(['renamed-echo']);
    });
});

describe('order (AC 10a)', function (): void {
    it('builds the context and the client list in stored order', function (array $stored): void {
        saveOverriddenTools('grouped', $stored);

        expect(ServerContextTools::names((new GroupedToolsServer(new FakeTransporter))->createContext()))->toBe($stored)
            ->and(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe($stored);
    })->with([
        'registered first' => [['echo-cortex-tool', 'renamed-echo', 'echo-mcp-tool', 'echo-request-tool']],
        'reversed' => [['echo-request-tool', 'echo-mcp-tool', 'renamed-echo', 'echo-cortex-tool']],
    ]);
});

describe('code tools', function (): void {
    it('serves a ToolSearch server as a flat list when overridden (AC 10b)', function (): void {
        saveOverriddenTools('grouped', ['echo-request-tool', 'echo-mcp-tool', 'renamed-echo']);

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-request-tool', 'echo-mcp-tool', 'renamed-echo']);

        // A grouped tool is called directly, not through execute_tools.
        GroupedToolsServer::tool(EchoRequestTool::class, ['message' => 'flat'])->assertOk()->assertSee('flat');
    });

    it('selects tools a host adds in its constructor or boot() (AC 10c)', function (): void {
        // Without an override the runtime tools are served as code tools.
        expect(ServerContextTools::listed(ConstructorToolsServer::tools()))->toBe(['echo-mcp-tool', 'renamed-echo', 'echo-request-tool']);

        writeOverriddenTools('constructor', ['echo-request-tool', 'renamed-echo']);

        expect(ServerContextTools::listed(ConstructorToolsServer::tools()))->toBe(['echo-request-tool', 'renamed-echo']);

        ConstructorToolsServer::tool(EchoRequestTool::class, ['message' => 'from boot'])->assertOk()->assertSee('from boot');
        ConstructorToolsServer::tool(NamedMcpTool::class, ['message' => 'from the constructor'])->assertOk()->assertSee('from the constructor');
    });
});

describe('tool visibility (AC 10e)', function (): void {
    it('keeps a selected code tool hidden when it does not register', function (): void {
        writeOverriddenTools('constructor', ['hidden-mcp-tool', 'echo-mcp-tool']);

        expect(ServerContextTools::listed(ConstructorToolsServer::tools()))->toBe(['echo-mcp-tool']);

        ConstructorToolsServer::tool(HiddenMcpTool::class)->assertHasErrors(['Tool [hidden-mcp-tool] not found.']);
    });

    it('keeps a selected registered tool hidden when it does not register', function (): void {
        app(ToolRegistry::class)->register('hidden-mcp-tool', HiddenMcpTool::class);

        saveOverriddenTools('grouped', ['hidden-mcp-tool', 'echo-mcp-tool']);

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool']);

        GroupedToolsServer::tool(HiddenMcpTool::class)->assertHasErrors(['Tool [hidden-mcp-tool] not found.']);
    });

    it('keeps the own gating of a Laravel AI tool and lets the request user reach it', function (): void {
        app(ToolRegistry::class)->register('gated-ai-tool', GatedAiTool::class);

        saveOverriddenTools('grouped', ['gated-ai-tool', 'echo-mcp-tool']);

        $gated = new AiToolAsMcpTool(new GatedAiTool);

        // A guest: the tool's own check refuses it.
        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool'])
            ->and(GatedAiTool::$asked)->toBeTrue()
            ->and(GatedAiTool::$seenUser)->toBeNull();

        GroupedToolsServer::tool($gated, ['message' => 'guest'])->assertHasErrors(['Tool [gated-ai-tool] not found.']);

        // A signed-in user: the same check sees them and serves the tool.
        GatedAiTool::reset();
        $user = new GenericUser(['id' => 7]);

        expect(ServerContextTools::listed(GroupedToolsServer::actingAs($user)->tools()))->toBe(['gated-ai-tool', 'echo-mcp-tool'])
            ->and(GatedAiTool::$seenUser?->getAuthIdentifier())->toBe(7);

        GroupedToolsServer::actingAs($user)->tool($gated, ['message' => 'signed in'])->assertOk()->assertSee('gated: signed in');
    });
});

describe('per-request memo (AC 10d, 10f)', function (): void {
    it('builds the context once per request', function (): void {
        saveOverriddenTools('grouped', ['renamed-echo']);

        $first = (new GroupedToolsServer(new FakeTransporter))->createContext();

        expect((new GroupedToolsServer(new FakeTransporter))->createContext())->toBe($first);

        app()->forgetScopedInstances();

        expect((new GroupedToolsServer(new FakeTransporter))->createContext())->not->toBe($first);
    });

    it('is scoped to the request, so the next request in the same process serves a new list', function (): void {
        saveOverriddenTools('grouped', ['renamed-echo']);

        $memo = app(ServerContextMemo::class);

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['renamed-echo']);

        // A change made in the same container (Octane, repeated client calls) ...
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', UpdateConcreteServerToolsData::from(['tools' => ['echo-mcp-tool', 'renamed-echo']], 'grouped'));

        // ... is served from the next request on.
        app()->forgetScopedInstances();

        expect(app(ServerContextMemo::class))->not->toBe($memo)
            ->and(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool', 'renamed-echo']);
    });
});

describe('servers that are not overridable (AC 12)', function (): void {
    it('ignores a cortex row with tools on the registered cortex route', function (): void {
        writeOverriddenTools('cortex', ['list-tools-tool']);

        config()->set('cortex.mcp.web.enabled', true);
        (new CortexServiceProvider(app()))->boot();

        $route = (string) config('cortex.mcp.web.route');

        expect(Mcp::getWebServer($route))->not->toBeNull();

        $response = $this->postJson('/'.ltrim($route, '/'), ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertOk();

        expect(array_column($response->json('result.tools'), 'name'))->toBe(['search_tools', 'execute_tools'])
            ->and(ServerContextTools::listed(CortexServer::tools()))->toBe(['search_tools', 'execute_tools']);
    });

    it('ignores a tools row for any server registered under the cortex name', function (): void {
        // EchoServer declares no tools, so an applied row would show.
        app(McpServerRegistry::class)->register('cortex', EchoServer::class);

        writeOverriddenTools('cortex', ['echo-cortex-tool']);

        expect(app(McpServerRegistry::class)->nameFor(EchoServer::class))->toBe('cortex')
            ->and(ServerContextTools::listed(EchoServer::tools()))->toBe([]);
    });

    it('serves its code tools for a server not registered with Cortex', function (): void {
        writeOverriddenTools('echo-server', ['echo-mcp-tool']);

        expect(ServerContextTools::listed(EchoServer::tools()))->toBe([]);
    });
});

it('serves the tool override through the base class and both trait names', function (): void {
    expect(class_uses_recursive(Server::class))->toContain(HasCortexOverrides::class)
        ->and(class_uses(HasVersionedInstructions::class))->toBe([HasCortexOverrides::class => HasCortexOverrides::class]);
});
