<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerContextRebuilder;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerToolResolver;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ToolsColumn;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\CollidingCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoMcpTool;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Fixtures\NamedMcpTool;
use RefactorCircus\Cortex\Tests\Fixtures\ThrowingCortexTool;
use RefactorCircus\Cortex\Tests\Support\ServerContextTools;

/*
 * Every warning-count test starts from an empty cache store, so no earlier
 * test's limiter key suppresses a warning (AC 16).
 */
beforeEach(function (): void {
    Cache::store('array')->flush();

    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
});

/**
 * Write the grouped server's override row directly (stale entries cannot be
 * saved through the update action), then start a new request.
 *
 * @param  list<string>  $tools
 */
function writeGroupedTools(array $tools, ?string $published = null): void
{
    $instruction = McpInstructionModel::query()->updateOrCreate(['server' => 'grouped'], ['tools' => $tools]);

    if ($published !== null) {
        $version = $instruction->versions()->create(['version' => 1, 'content' => $published]);
        $instruction->published_version_id = $version->getKey();
        $instruction->save();
    }

    $cache = app(PublicationCache::class);
    $cache->forget($cache->mcpToolsKey(), $cache->mcpInstructionsKey());

    app()->forgetScopedInstances();
}

/**
 * Start a new request in the same process.
 */
function nextGroupedRequest(): void
{
    app()->forgetScopedInstances();
}

/**
 * @param  array<string, mixed>  $context
 */
function assertWarnedOnce(array $context, ?string $messagePart = null): void
{
    // The argument filter comes first: Mockery checks the count as soon as
    // it is chained.
    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $message, array $logged = []): bool => $logged === $context
            && ($messagePart === null || str_contains($message, $messagePart)),
    )->once();
}

describe('stale and failing entries (AC 16)', function (): void {
    it('skips an entry that no longer resolves, logs it and serves the rest', function (): void {
        Log::spy();

        writeGroupedTools(['echo-mcp-tool', 'gone-tool']);

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool']);

        GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'kept'])->assertOk()->assertSee('kept');

        assertWarnedOnce(['server' => 'grouped', 'entry' => 'gone-tool'], 'gone-tool');
        Log::shouldHaveReceived('warning')->once();

        // The silent report names it for the resource (no warning of its own).
        $report = app(ServerToolResolver::class)->resolve(
            app(ServerContextRebuilder::class)->tools((new GroupedToolsServer(new FakeTransporter))->createContext()),
            ['echo-mcp-tool', 'gone-tool'],
        );

        expect($report['missing'])->toBe(['gone-tool']);
        Log::shouldHaveReceived('warning')->once();
    });

    it('skips an entry whose tool throws while built, logs the message and serves the rest', function (): void {
        app(ToolRegistry::class)->register('throwing-cortex-tool', ThrowingCortexTool::class);

        app(UpdateConcreteServerToolsAction::class)->execute('grouped', UpdateConcreteServerToolsData::from([
            'tools' => ['throwing-cortex-tool', 'echo-mcp-tool', 'renamed-echo'],
        ], 'grouped'));
        nextGroupedRequest();

        Log::spy();

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool', 'renamed-echo']);

        GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'one'])->assertOk()->assertSee('one');
        GroupedToolsServer::tool(NamedMcpTool::class, ['message' => 'two'])->assertOk()->assertSee('two');

        assertWarnedOnce(['server' => 'grouped', 'entry' => 'throwing-cortex-tool', 'message' => ThrowingCortexTool::MESSAGE]);
        Log::shouldHaveReceived('warning')->once();

        $report = app(ServerToolResolver::class)->resolve(
            app(ServerContextRebuilder::class)->tools((new GroupedToolsServer(new FakeTransporter))->createContext()),
            ['throwing-cortex-tool', 'echo-mcp-tool'],
        );

        expect($report['missing'])->toBe(['throwing-cortex-tool'])
            ->and($report['failed'])->toBe(['throwing-cortex-tool' => ThrowingCortexTool::MESSAGE]);
    });

    it('rate-limits a stale entry across requests until the window passes', function (): void {
        config()->set('cortex.mcp.warning_ttl', 120);
        Log::spy();

        writeGroupedTools(['gone-tool']);

        GroupedToolsServer::tools();
        nextGroupedRequest();
        GroupedToolsServer::tools();

        Log::shouldHaveReceived('warning')->once();

        $this->travel((int) config('cortex.mcp.warning_ttl') + 1)->seconds();
        nextGroupedRequest();
        GroupedToolsServer::tools();

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context = []): bool => $context === ['server' => 'grouped', 'entry' => 'gone-tool'],
        )->twice();
    });

    it('limits different entries on the same server independently', function (): void {
        Log::spy();

        writeGroupedTools(['gone-a']);
        GroupedToolsServer::tools();

        writeGroupedTools(['gone-a', 'gone-b']);
        GroupedToolsServer::tools();

        assertWarnedOnce(['server' => 'grouped', 'entry' => 'gone-a']);
        assertWarnedOnce(['server' => 'grouped', 'entry' => 'gone-b']);
        Log::shouldHaveReceived('warning')->twice();
    });

    it('serves and warns when the cache store throws', function (): void {
        $store = new class implements Store
        {
            /**
             * @var list<string>
             */
            public array $calls = [];

            public function add(string $key, mixed $value, int $seconds): bool
            {
                return $this->fail(__FUNCTION__);
            }

            public function get($key)
            {
                return $this->fail(__FUNCTION__);
            }

            public function many(array $keys)
            {
                return $this->fail(__FUNCTION__);
            }

            public function put($key, $value, $seconds)
            {
                return $this->fail(__FUNCTION__);
            }

            public function putMany(array $values, $seconds)
            {
                return $this->fail(__FUNCTION__);
            }

            public function increment($key, $value = 1)
            {
                return $this->fail(__FUNCTION__);
            }

            public function decrement($key, $value = 1)
            {
                return $this->fail(__FUNCTION__);
            }

            public function forever($key, $value)
            {
                return $this->fail(__FUNCTION__);
            }

            public function touch($key, $seconds)
            {
                return $this->fail(__FUNCTION__);
            }

            public function forget($key)
            {
                return $this->fail(__FUNCTION__);
            }

            public function flush()
            {
                return $this->fail(__FUNCTION__);
            }

            public function getPrefix()
            {
                return '';
            }

            private function fail(string $call): never
            {
                $this->calls[] = $call;

                throw new RuntimeException('Cache store is down.');
            }
        };

        writeGroupedTools(['echo-mcp-tool', 'gone-tool']);

        Cache::extend('throwing', fn () => Cache::repository($store));
        config()->set('cache.stores.throwing', ['driver' => 'throwing']);
        config()->set('cortex.cache.store', 'throwing');
        nextGroupedRequest();

        Log::spy();

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool'])
            ->and($store->calls)->toContain('add');

        GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'still served'])->assertOk()->assertSee('still served');

        assertWarnedOnce(['server' => 'grouped', 'entry' => 'gone-tool']);
    });
});

describe('ambiguous entries (AC 10d)', function (): void {
    it('serves the code tool for an entry that also names a registered tool, and logs it', function (): void {
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', UpdateConcreteServerToolsData::from([
            'tools' => ['echo-mcp-tool', 'renamed-echo'],
        ], 'grouped'));

        // Registered after the override was saved.
        app(ToolRegistry::class)->register('echo-mcp-tool', CollidingCortexTool::class);
        nextGroupedRequest();

        Log::spy();

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool', 'renamed-echo']);

        GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'code wins'])
            ->assertOk()
            ->assertSee('code wins')
            ->assertDontSee(CollidingCortexTool::ANSWER);

        assertWarnedOnce(['server' => 'grouped', 'entry' => 'echo-mcp-tool'], 'echo-mcp-tool');
        Log::shouldHaveReceived('warning')->once();

        $report = app(ServerToolResolver::class)->resolve(
            app(ServerContextRebuilder::class)->tools((new GroupedToolsServer(new FakeTransporter))->createContext()),
            ['echo-mcp-tool', 'renamed-echo'],
        );

        expect($report['ambiguous'])->toBe(['echo-mcp-tool'])
            ->and($report['missing'])->toBe([]);
        Log::shouldHaveReceived('warning')->once();
    });

    it('logs exactly one warning per ambiguous entry for one client request', function (): void {
        writeGroupedTools(['echo-mcp-tool', 'renamed-echo', 'echo-request-tool']);

        app(ToolRegistry::class)->register('echo-mcp-tool', CollidingCortexTool::class);
        app(ToolRegistry::class)->register('renamed-echo', CollidingCortexTool::class);

        Log::spy();

        GroupedToolsServer::tools();

        assertWarnedOnce(['server' => 'grouped', 'entry' => 'echo-mcp-tool']);
        assertWarnedOnce(['server' => 'grouped', 'entry' => 'renamed-echo']);
        Log::shouldHaveReceived('warning')->twice();
    });

    it('does not repeat the warnings when the context is built twice in one request', function (): void {
        writeGroupedTools(['echo-mcp-tool']);
        app(ToolRegistry::class)->register('echo-mcp-tool', CollidingCortexTool::class);

        Log::spy();

        $first = (new GroupedToolsServer(new FakeTransporter))->createContext();

        // Even with the limiter's keys gone, the memo answers.
        Cache::store('array')->flush();

        expect((new GroupedToolsServer(new FakeTransporter))->createContext())->toBe($first);

        Log::shouldHaveReceived('warning')->once();

        // The next request builds again; with the keys gone it warns again.
        Cache::store('array')->flush();
        nextGroupedRequest();
        (new GroupedToolsServer(new FakeTransporter))->createContext();

        Log::shouldHaveReceived('warning')->twice();
    });
});

it('serves the code tools for a corrupt stored value and logs its type (AC 18)', function (): void {
    DB::table(Tables::name('mcp_instructions'))->insert([
        'id' => (string) Str::ulid(),
        'server' => 'grouped',
        'tools' => '"echo-mcp-tool"',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Log::spy();

    expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool', 'search_tools', 'execute_tools']);

    GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'code'])->assertOk()->assertSee('code');

    assertWarnedOnce(['server' => 'grouped', 'value_type' => 'string']);
    Log::shouldHaveReceived('warning')->once();
});

it('serves the code tools with the instructions override and warns once without the tools column (AC 8)', function (): void {
    writeGroupedTools(['renamed-echo'], 'Published grouped instructions.');

    Schema::table(Tables::name('mcp_instructions'), function (Blueprint $table): void {
        $table->dropColumn('tools');
    });

    app()->forgetInstance(ToolsColumn::class);
    nextGroupedRequest();

    Log::spy();

    expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool', 'search_tools', 'execute_tools'])
        ->and((new GroupedToolsServer(new FakeTransporter))->createContext()->instructions)->toBe('Published grouped instructions.');

    GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'no column'])->assertOk()->assertSee('no column');

    nextGroupedRequest();
    GroupedToolsServer::tools();

    assertWarnedOnce(['server' => 'grouped'], 'add_tools_to_cortex_mcp_instructions');
    Log::shouldHaveReceived('warning')->once();
});

describe('a failing context rebuild (D-028, D-047)', function (): void {
    it('serves the code tools with the instructions override and logs the failure', function (): void {
        writeGroupedTools(['renamed-echo'], 'Published grouped instructions.');

        app()->bind(ServerContextRebuilder::class, fn () => throw new RuntimeException('The rebuild broke.'));

        Log::spy();

        expect(ServerContextTools::listed(GroupedToolsServer::tools()))->toBe(['echo-mcp-tool', 'search_tools', 'execute_tools'])
            ->and((new GroupedToolsServer(new FakeTransporter))->createContext()->instructions)->toBe('Published grouped instructions.');

        GroupedToolsServer::tool(EchoMcpTool::class, ['message' => 'fallback'])->assertOk()->assertSee('fallback');

        assertWarnedOnce(['server' => 'grouped', 'message' => 'The rebuild broke.'], RuntimeException::class);
        Log::shouldHaveReceived('warning')->once();
    });

    it('rate-limits the failure by server and exception class', function (): void {
        writeGroupedTools(['renamed-echo']);

        app()->bind(ServerContextRebuilder::class, fn () => throw new RuntimeException('The rebuild broke.'));

        Log::spy();

        GroupedToolsServer::tools();
        nextGroupedRequest();
        GroupedToolsServer::tools();

        Log::shouldHaveReceived('warning')->once();

        // Another exception class logs on its own.
        app()->bind(ServerContextRebuilder::class, fn () => throw new LogicException('The rebuild broke differently.'));
        nextGroupedRequest();
        GroupedToolsServer::tools();

        assertWarnedOnce(['server' => 'grouped', 'message' => 'The rebuild broke differently.'], LogicException::class);
        Log::shouldHaveReceived('warning')->twice();
    });
});
