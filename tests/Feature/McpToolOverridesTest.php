<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpInstructionOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpToolOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ToolsColumn;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Support\Tables;

beforeEach(function (): void {
    // No warning key left by an earlier test may suppress a warning here.
    Cache::store('array')->flush();
});

function freshToolOverrides(): McpToolOverrides
{
    app()->forgetScopedInstances();

    return app(McpToolOverrides::class);
}

/**
 * Write a raw json value into the tools column, bypassing the model cast.
 */
function insertRawTools(string $server, string $json): void
{
    DB::table(Tables::name('mcp_instructions'))->insert([
        'id' => (string) Str::ulid(),
        'server' => $server,
        'tools' => $json,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function publishedInstruction(string $server, ?array $tools): McpInstructionModel
{
    $instruction = McpInstructionModel::query()->create(['server' => $server, 'tools' => $tools]);
    $version = $instruction->versions()->create(['version' => 1, 'content' => "Published {$server} instructions."]);
    $instruction->published_version_id = $version->getKey();
    $instruction->save();

    return $instruction;
}

it('maps every row with a tool list, published or not', function (): void {
    McpInstructionModel::query()->create(['server' => 'echo', 'tools' => ['echo-mcp-tool', 'renamed-echo']]);
    publishedInstruction('plain', ['echo-request-tool']);
    publishedInstruction('grouped', null);

    $overrides = freshToolOverrides();

    expect($overrides->for('echo'))->toBe(['echo-mcp-tool', 'renamed-echo'])
        ->and($overrides->for('plain'))->toBe(['echo-request-tool'])
        ->and($overrides->for('grouped'))->toBeNull()
        ->and($overrides->for('missing'))->toBeNull();
});

it('keeps the stored order of a tool list', function (): void {
    McpInstructionModel::query()->create(['server' => 'echo', 'tools' => ['b', 'a', 'c']]);

    expect(freshToolOverrides()->for('echo'))->toBe(['b', 'a', 'c']);
});

it('keeps an empty tool list apart from no list', function (): void {
    McpInstructionModel::query()->create(['server' => 'echo', 'tools' => []]);

    expect(freshToolOverrides()->for('echo'))->toBe([]);
});

it('caches the tool lists under their own key until it is forgotten', function (): void {
    $cache = app(PublicationCache::class);

    McpInstructionModel::query()->create(['server' => 'echo', 'tools' => ['first']]);

    expect(freshToolOverrides()->for('echo'))->toBe(['first'])
        ->and(Cache::store('array')->get($cache->mcpToolsKey()))->toBe(['echo' => ['first']]);

    // A write that bypasses the actions is not seen until the key is forgotten.
    McpInstructionModel::query()->where('server', 'echo')->firstOrFail()->update(['tools' => ['second']]);

    expect(freshToolOverrides()->for('echo'))->toBe(['first']);

    $cache->forget($cache->mcpToolsKey());

    expect(freshToolOverrides()->for('echo'))->toBe(['second']);
});

it('keys the tool lists in the publication cache family, apart from instructions', function (): void {
    $cache = app(PublicationCache::class);

    preg_match('/\{[^}]+\}/', $cache->mcpToolsKey(), $keyTag);
    preg_match('/\{[^}]+\}/', 'illuminate:cache:flexible:created:'.$cache->mcpToolsKey(), $twinTag);

    expect($cache->mcpToolsKey())->toStartWith('{cortex.published}.')
        ->and($cache->mcpToolsKey())->not->toBe($cache->mcpInstructionsKey())
        ->and($twinTag[0])->toBe($keyTag[0]);
});

it('reads the map once per request', function (): void {
    McpInstructionModel::query()->create(['server' => 'echo', 'tools' => ['first']]);

    $overrides = freshToolOverrides();

    expect($overrides->for('echo'))->toBe(['first'])
        ->and(app(McpToolOverrides::class))->toBe($overrides);

    app(PublicationCache::class)->forget(app(PublicationCache::class)->mcpToolsKey());
    McpInstructionModel::query()->where('server', 'echo')->firstOrFail()->update(['tools' => ['second']]);

    expect($overrides->for('echo'))->toBe(['first'])
        ->and(freshToolOverrides()->for('echo'))->toBe(['second']);
});

it('reads a stored value that is not a list as no override and warns with its type', function (string $json, string $type): void {
    Log::spy();

    insertRawTools('echo', $json);

    expect(freshToolOverrides()->for('echo'))->toBeNull();

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => str_contains($message, 'echo')
            && $context === ['server' => 'echo', 'value_type' => $type],
    );
})->with([
    'string' => ['"echo-mcp-tool"', 'string'],
    'int' => ['42', 'int'],
    'bool' => ['true', 'bool'],
]);

it('rate-limits the corrupt value warning across requests', function (): void {
    Log::spy();

    insertRawTools('echo', '"echo-mcp-tool"');

    freshToolOverrides()->for('echo');
    freshToolOverrides()->for('echo');

    Log::shouldHaveReceived('warning')->once();

    Cache::store('array')->flush();
    freshToolOverrides()->for('echo');

    Log::shouldHaveReceived('warning')->twice();
});

it('serves the map and logs the corrupt value warning when the cache store throws', function (): void {
    $store = new class implements Store
    {
        public int $calls = 0;

        public function add(string $key, mixed $value, int $seconds): bool
        {
            return $this->fail();
        }

        public function get($key)
        {
            return $this->fail();
        }

        public function many(array $keys)
        {
            return $this->fail();
        }

        public function put($key, $value, $seconds)
        {
            return $this->fail();
        }

        public function putMany(array $values, $seconds)
        {
            return $this->fail();
        }

        public function increment($key, $value = 1)
        {
            return $this->fail();
        }

        public function decrement($key, $value = 1)
        {
            return $this->fail();
        }

        public function forever($key, $value)
        {
            return $this->fail();
        }

        public function touch($key, $seconds)
        {
            return $this->fail();
        }

        public function forget($key)
        {
            return $this->fail();
        }

        public function flush()
        {
            return $this->fail();
        }

        public function getPrefix()
        {
            return '';
        }

        private function fail(): never
        {
            $this->calls++;

            throw new RuntimeException('Cache store is down.');
        }
    };

    Cache::extend('throwing', fn () => Cache::repository($store));
    config()->set('cache.stores.throwing', ['driver' => 'throwing']);
    config()->set('cortex.cache.store', 'throwing');

    McpInstructionModel::query()->create(['server' => 'echo', 'tools' => ['echo-mcp-tool']]);
    insertRawTools('plain', '"echo-mcp-tool"');

    Log::spy();

    $overrides = freshToolOverrides();

    expect($overrides->for('echo'))->toBe(['echo-mcp-tool'])
        ->and($overrides->for('plain'))->toBeNull()
        ->and($store->calls)->toBeGreaterThan(0);

    Log::shouldHaveReceived('warning')->once()->with(Mockery::type('string'), ['server' => 'plain', 'value_type' => 'string']);
});

describe('without the tools column', function (): void {
    beforeEach(function (): void {
        publishedInstruction('echo', null);

        Schema::table(Tables::name('mcp_instructions'), function (Blueprint $table): void {
            $table->dropColumn('tools');
        });

        app()->forgetInstance(ToolsColumn::class);
    });

    it('reads no tool override, keeps the instructions override and warns naming the migration', function (): void {
        Log::spy();

        expect(freshToolOverrides()->for('echo'))->toBeNull()
            ->and(app(McpInstructionOverrides::class)->for('echo'))->toBe('Published echo instructions.');

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => str_contains($message, 'add_tools_to_cortex_mcp_instructions')
                && $context === ['server' => 'echo'],
        );
    });

    it('rate-limits the missing column warning across requests', function (): void {
        Log::spy();

        freshToolOverrides()->for('echo');
        freshToolOverrides()->for('echo');

        Log::shouldHaveReceived('warning')->once();

        $this->travel((int) config('cortex.mcp.warning_ttl') + 1)->seconds();
        freshToolOverrides()->for('echo');

        Log::shouldHaveReceived('warning')->twice();
    });
});

describe('when the column check throws', function (): void {
    it('reads no tool override, warns once within the window and checks again on the next call', function (): void {
        McpInstructionModel::query()->create(['server' => 'echo', 'tools' => ['echo-mcp-tool']]);
        app()->forgetInstance(ToolsColumn::class);

        $down = true;
        $checks = 0;

        // The schema query fails as it would with the connection down.
        DB::connection()->beforeExecuting(function () use (&$down, &$checks): void {
            if (! $down) {
                return;
            }

            $checks++;

            throw new RuntimeException('Connection is down.');
        });

        Log::spy();

        $column = app(ToolsColumn::class);

        expect(freshToolOverrides()->for('echo'))->toBeNull()
            ->and(freshToolOverrides()->for('echo'))->toBeNull()
            // Nothing was memoized: each call asked the schema again.
            ->and($checks)->toBe(2)
            ->and(app(ToolsColumn::class))->toBe($column);

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => str_contains($message, 'echo')
                && $context === ['server' => 'echo', 'message' => 'Connection is down.'],
        );

        $cache = app(PublicationCache::class);

        expect(Cache::store('array')->has($cache->warningKey('echo', 'column-check:'.RuntimeException::class)))->toBeTrue();

        // Once the check succeeds the same process sees the column and the stored list.
        $down = false;

        expect(freshToolOverrides()->for('echo'))->toBe(['echo-mcp-tool'])
            ->and($column->exists())->toBeTrue();

        Log::shouldHaveReceived('warning')->once();
    });

    it('keeps the instructions override while the column check throws', function (): void {
        publishedInstruction('echo', ['echo-mcp-tool']);
        app()->forgetInstance(ToolsColumn::class);

        // Warm the instructions map, then take the schema query down.
        expect(app(McpInstructionOverrides::class)->for('echo'))->toBe('Published echo instructions.');

        $down = true;

        DB::connection()->beforeExecuting(function () use (&$down): void {
            if ($down) {
                throw new RuntimeException('Connection is down.');
            }
        });

        try {
            expect(freshToolOverrides()->for('echo'))->toBeNull()
                ->and(app(McpInstructionOverrides::class)->for('echo'))->toBe('Published echo instructions.');
        } finally {
            // The test's own rollback must reach the database.
            $down = false;
        }
    });
});

it('checks the column once per process', function (): void {
    $column = app(ToolsColumn::class);

    expect($column->exists())->toBeTrue()
        ->and(app(ToolsColumn::class))->toBe($column);

    Schema::table(Tables::name('mcp_instructions'), function (Blueprint $table): void {
        $table->dropColumn('tools');
    });

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    // The memo answers; no schema query is made again.
    expect($column->exists())->toBeTrue()
        ->and($queries)->toBe(0);

    app()->forgetInstance(ToolsColumn::class);

    expect(app(ToolsColumn::class)->exists())->toBeFalse();
});

it('checks the column on the model connection, not the default one', function (): void {
    config()->set('database.connections.tenant', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    // The tenant database has the table but was never given the tools column.
    Schema::connection('tenant')->create(Tables::name('mcp_instructions'), function (Blueprint $table): void {
        $table->ulid('id')->primary();
        $table->string('server')->unique();
        $table->ulid('published_version_id')->nullable();
        $table->timestamps();
    });

    $resolver = new ConnectionResolver(['tenant' => DB::connection('tenant')]);
    $resolver->setDefaultConnection('tenant');
    $original = Model::getConnectionResolver();

    Model::setConnectionResolver($resolver);

    try {
        expect((new McpInstructionModel)->getConnection()->getName())->toBe('tenant')
            ->and(DB::connection()->getName())->toBe('testing')
            ->and(app(ToolsColumn::class)->exists())->toBeFalse();
    } finally {
        Model::setConnectionResolver($original);
    }

    // The default connection keeps its own answer.
    expect(app(ToolsColumn::class)->exists())->toBeTrue();
});
