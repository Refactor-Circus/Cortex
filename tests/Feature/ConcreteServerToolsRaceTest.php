<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Support\FileDatabaseMigrations;

// D-023, D-031: no wrapping transaction, file-based SQLite only.
uses(FileDatabaseMigrations::class);

beforeEach(function (): void {
    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
});

it('runs on a file-based SQLite database without a wrapping transaction', function (): void {
    expect(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(DB::connection()->getDatabaseName())->not->toBe(':memory:')
        ->and(is_file(DB::connection()->getDatabaseName()))->toBeTrue()
        ->and(DB::transactionLevel())->toBe(0);
});

it('survives two first saves colliding on the server unique index', function (): void {
    $competed = false;

    // Another first save for the same server lands between the action's
    // read and its insert, once.
    McpInstructionModel::creating(function (McpInstructionModel $instruction) use (&$competed): void {
        if ($competed || $instruction->server !== 'grouped') {
            return;
        }

        $competed = true;

        DB::table(Tables::name('mcp_instructions'))->insert([
            'id' => (string) Str::ulid(),
            'server' => 'grouped',
            'tools' => json_encode(['echo-request-tool']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $instruction = app(UpdateConcreteServerToolsAction::class)
        ->execute('grouped', new UpdateConcreteServerToolsData(['renamed-echo', 'echo-mcp-tool']));

    $rows = McpInstructionModel::query()->where('server', 'grouped')->get();

    expect($competed)->toBeTrue()
        ->and($rows)->toHaveCount(1)
        ->and($rows->first()?->tools)->toBe(['renamed-echo', 'echo-mcp-tool'])
        ->and($instruction->tools)->toBe(['renamed-echo', 'echo-mcp-tool']);
});
