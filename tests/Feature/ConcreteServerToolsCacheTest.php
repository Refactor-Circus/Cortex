<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ClearConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\DeleteMcpInstructionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\ClearConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpInstructionOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpToolOverrides;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Support\FileDatabaseMigrations;

// D-023: DB::afterCommit() is exercised for real, with no wrapping transaction.
uses(FileDatabaseMigrations::class);

/**
 * The publication cache's store, recording the transaction level at every
 * `forget`, so a test sees whether a forget ran inside a transaction.
 */
final class TransactionLevelRecordingStore extends ArrayStore
{
    /**
     * @var list<array{key: string, level: int}>
     */
    public array $forgets = [];

    public bool $failForgets = false;

    public function forget($key)
    {
        $this->forgets[] = ['key' => (string) $key, 'level' => DB::transactionLevel()];

        if ($this->failForgets) {
            throw new RuntimeException('Cache store is down.');
        }

        return parent::forget($key);
    }
}

beforeEach(function (): void {
    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);

    $this->store = new TransactionLevelRecordingStore;
    $store = $this->store;

    Cache::extend('recording', fn () => Cache::repository($store));
    config()->set('cache.stores.recording', ['driver' => 'recording']);
    config()->set('cortex.cache.store', 'recording');
});

/**
 * Read the tool overrides as a new request would.
 *
 * @return list<string>|null
 */
function cachedServerTools(string $server = 'grouped'): ?array
{
    app()->forgetScopedInstances();

    return app(McpToolOverrides::class)->for($server);
}

function cachedServerInstructions(string $server = 'grouped'): ?string
{
    app()->forgetScopedInstances();

    return app(McpInstructionOverrides::class)->for($server);
}

/**
 * An override row with published instructions and three versions.
 */
function cachedServerWithInstructions(?array $tools): McpInstructionModel
{
    $instruction = McpInstructionModel::query()->create(['server' => 'grouped', 'tools' => $tools]);

    foreach ([1, 2, 3] as $number) {
        $version = $instruction->versions()->create(['version' => $number, 'content' => "Instructions v{$number}."]);
    }

    $instruction->published_version_id = $version->getKey();
    $instruction->save();

    return $instruction->refresh();
}

/**
 * @return list<int>
 */
function forgetLevels(TransactionLevelRecordingStore $store): array
{
    return array_column($store->forgets, 'level');
}

/**
 * @return list<string>
 */
function forgottenKeys(TransactionLevelRecordingStore $store): array
{
    return array_values(array_unique(array_column($store->forgets, 'key')));
}

describe('update', function (): void {
    it('(a) serves the new list on the next read', function (): void {
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['echo-mcp-tool']));

        expect(cachedServerTools())->toBe(['echo-mcp-tool']);

        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['renamed-echo']));

        expect(cachedServerTools())->toBe(['renamed-echo']);
    });

    it('(b) forgets the tools key after commit', function (): void {
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['echo-mcp-tool']));

        expect($this->store->forgets)->not->toBeEmpty()
            ->and(forgetLevels($this->store))->each->toBe(0)
            ->and(forgottenKeys($this->store))->toBe([app(PublicationCache::class)->mcpToolsKey()]);
    });

    it('(c) forgets nothing and keeps the old list when it rolls back', function (): void {
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['echo-mcp-tool']));
        expect(cachedServerTools())->toBe(['echo-mcp-tool']);
        $this->store->forgets = [];

        McpInstructionModel::saved(function (): never {
            throw new RuntimeException('Saved listener failed.');
        });

        expect(fn () => app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['renamed-echo'])))
            ->toThrow(RuntimeException::class, 'Saved listener failed.');

        expect(DB::table((new McpInstructionModel)->getTable())->where('server', 'grouped')->value('tools'))->toBe('["echo-mcp-tool"]')
            ->and($this->store->forgets)->toBe([])
            ->and(cachedServerTools())->toBe(['echo-mcp-tool']);
    });

    it('succeeds and logs when the after-commit forget fails', function (): void {
        Log::spy();
        $this->store->failForgets = true;

        $instruction = app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['echo-mcp-tool']));

        expect($instruction->tools)->toBe(['echo-mcp-tool'])
            ->and(McpInstructionModel::query()->where('server', 'grouped')->firstOrFail()->tools)->toBe(['echo-mcp-tool'])
            ->and($this->store->forgets)->not->toBeEmpty();

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => ($context['key'] ?? null) === app(PublicationCache::class)->mcpToolsKey()
                && ($context['message'] ?? null) === 'Cache store is down.',
        )->once();
    });
});

describe('clear', function (): void {
    it('(a) serves the code tools on the next read', function (): void {
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['echo-mcp-tool']));
        expect(cachedServerTools())->toBe(['echo-mcp-tool']);

        app(ClearConcreteServerToolsAction::class)->execute('grouped', new ClearConcreteServerToolsData);

        expect(cachedServerTools())->toBeNull();
    });

    it('(b) forgets after commit: the tools key, and the instructions key when the row goes', function (): void {
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['echo-mcp-tool']));
        $this->store->forgets = [];

        app(ClearConcreteServerToolsAction::class)->execute('grouped', new ClearConcreteServerToolsData);

        $cache = app(PublicationCache::class);

        expect($this->store->forgets)->not->toBeEmpty()
            ->and(forgetLevels($this->store))->each->toBe(0)
            ->and(forgottenKeys($this->store))->toEqualCanonicalizing([$cache->mcpToolsKey(), $cache->mcpInstructionsKey()]);
    });

    it('(b) forgets only the tools key when the row stays', function (): void {
        cachedServerWithInstructions(['echo-mcp-tool']);
        $this->store->forgets = [];

        app(ClearConcreteServerToolsAction::class)->execute('grouped', new ClearConcreteServerToolsData);

        expect(forgetLevels($this->store))->each->toBe(0)
            ->and(forgottenKeys($this->store))->toBe([app(PublicationCache::class)->mcpToolsKey()]);
    });

    it('(c) forgets nothing and keeps the list when it rolls back', function (): void {
        cachedServerWithInstructions(['echo-mcp-tool']);
        expect(cachedServerTools())->toBe(['echo-mcp-tool']);
        $this->store->forgets = [];

        McpInstructionModel::saved(function (): never {
            throw new RuntimeException('Saved listener failed.');
        });

        expect(fn () => app(ClearConcreteServerToolsAction::class)->execute('grouped', new ClearConcreteServerToolsData))
            ->toThrow(RuntimeException::class, 'Saved listener failed.');

        expect(McpInstructionModel::query()->where('server', 'grouped')->firstOrFail()->tools)->toBe(['echo-mcp-tool'])
            ->and($this->store->forgets)->toBe([])
            ->and(cachedServerTools())->toBe(['echo-mcp-tool']);
    });

    it('succeeds and logs when the after-commit forget fails', function (): void {
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['echo-mcp-tool']));

        Log::spy();
        $this->store->failForgets = true;

        app(ClearConcreteServerToolsAction::class)->execute('grouped', new ClearConcreteServerToolsData);

        expect(McpInstructionModel::query()->where('server', 'grouped')->exists())->toBeFalse();

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => ($context['message'] ?? null) === 'Cache store is down.',
        )->twice();
    });
});

describe('delete instructions', function (): void {
    it('(a) serves code instructions and keeps serving the tool list on the next read', function (): void {
        $instruction = cachedServerWithInstructions(['echo-mcp-tool']);

        expect(cachedServerInstructions())->toBe('Instructions v3.')
            ->and(cachedServerTools())->toBe(['echo-mcp-tool']);

        app(DeleteMcpInstructionAction::class)->execute($instruction);

        expect(cachedServerInstructions())->toBeNull()
            ->and(cachedServerTools())->toBe(['echo-mcp-tool']);
    });

    it('(b) forgets both keys after commit, whether or not the row goes', function (bool $keepsTools): void {
        $instruction = cachedServerWithInstructions($keepsTools ? ['echo-mcp-tool'] : null);
        $this->store->forgets = [];

        app(DeleteMcpInstructionAction::class)->execute($instruction);

        $cache = app(PublicationCache::class);

        expect(McpInstructionModel::query()->where('server', 'grouped')->exists())->toBe($keepsTools)
            ->and($this->store->forgets)->not->toBeEmpty()
            ->and(forgetLevels($this->store))->each->toBe(0)
            ->and(forgottenKeys($this->store))->toEqualCanonicalizing([$cache->mcpToolsKey(), $cache->mcpInstructionsKey()]);
    })->with([
        'row kept for its tools' => [true],
        'row removed' => [false],
    ]);

    it('(c) rolls back when the second version delete throws', function (): void {
        $instruction = cachedServerWithInstructions(null);
        expect(cachedServerInstructions())->toBe('Instructions v3.');
        $this->store->forgets = [];

        $deletes = 0;

        McpInstructionVersionModel::deleting(function () use (&$deletes): void {
            if (++$deletes === 2) {
                throw new RuntimeException('Second version delete failed.');
            }
        });

        expect(fn () => app(DeleteMcpInstructionAction::class)->execute($instruction))
            ->toThrow(RuntimeException::class, 'Second version delete failed.');

        $row = McpInstructionModel::query()->where('server', 'grouped')->first();

        expect($deletes)->toBe(2)
            ->and($row)->not->toBeNull()
            ->and($row?->published_version_id)->toBe($instruction->published_version_id)
            ->and(McpInstructionVersionModel::query()->count())->toBe(3)
            ->and($this->store->forgets)->toBe([])
            ->and(cachedServerInstructions())->toBe('Instructions v3.');
    });

    it('succeeds and logs when the after-commit forget fails', function (): void {
        $instruction = cachedServerWithInstructions(['echo-mcp-tool']);

        Log::spy();
        $this->store->failForgets = true;

        app(DeleteMcpInstructionAction::class)->execute($instruction);

        expect(McpInstructionVersionModel::query()->count())->toBe(0);

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => ($context['message'] ?? null) === 'Cache store is down.',
        )->twice();
    });
});
