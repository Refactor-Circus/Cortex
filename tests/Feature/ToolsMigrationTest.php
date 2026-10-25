<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Support\Tables;

// SQLite only (D-031). The staged run uses its own in-memory connection, so
// the suite's migrated `testing` database and its teardown rollback are left
// alone.

/**
 * @return array{0: list<string>, 1: string}
 */
function toolsMigrationSteps(): array
{
    $directory = dirname(__DIR__, 2).'/database/migrations';

    $matches = glob($directory.'/*_add_tools_to_cortex_mcp_instructions.php') ?: [];

    expect($matches)->toHaveCount(1);

    $target = $matches[0];
    $all = glob($directory.'/*.php') ?: [];
    sort($all);

    $before = array_values(array_filter($all, fn (string $file): bool => strcmp(basename($file), basename($target)) < 0));

    return [$before, $target];
}

function stagedMigrate(string $path, string $command = 'migrate', array $options = []): void
{
    $exit = Artisan::call($command, [
        '--database' => 'staged',
        '--path' => $path,
        '--realpath' => true,
        '--force' => true,
        ...$options,
    ]);

    expect($exit)->toBe(0, Artisan::output());
}

/**
 * @return list<string>
 */
function toolsColumns(?string $connection = null): array
{
    return array_values(array_filter(
        Schema::connection($connection)->getColumnListing(Tables::name('mcp_instructions')),
        fn (string $column): bool => $column === 'tools',
    ));
}

beforeEach(function (): void {
    config()->set('database.connections.staged', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
});

it('adds a nullable tools column to existing rows and drops it on rollback', function (): void {
    [$before, $target] = toolsMigrationSteps();

    expect($before)->not->toBeEmpty();

    foreach ($before as $step) {
        stagedMigrate($step);
    }

    $table = Tables::name('mcp_instructions');
    $id = (string) Str::ulid();

    expect(toolsColumns('staged'))->toBe([]);

    DB::connection('staged')->table($table)->insert([
        'id' => $id,
        'server' => 'echo',
        'published_version_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    stagedMigrate($target);

    $row = DB::connection('staged')->table($table)->where('id', $id)->first();

    expect(toolsColumns('staged'))->toBe(['tools'])
        ->and($row)->not->toBeNull()
        ->and($row->tools)->toBeNull();

    stagedMigrate($target, 'migrate:rollback', ['--step' => 1]);

    expect(toolsColumns('staged'))->toBe([])
        ->and(DB::connection('staged')->table($table)->where('id', $id)->value('server'))->toBe('echo');
});

it('is a no-op when the column already exists or is already gone', function (): void {
    [, $target] = toolsMigrationSteps();

    // The suite's database already ran every migration, the tools one too.
    expect(toolsColumns())->toBe(['tools']);

    $migration = require $target;

    $migration->up();
    $migration->up();

    expect(toolsColumns())->toBe(['tools']);

    $migration->down();
    $migration->down();

    expect(toolsColumns())->toBe([]);

    $migration->up();

    expect(toolsColumns())->toBe(['tools']);
});

it('casts the tools column to an array and lets it be mass assigned', function (): void {
    $instruction = McpInstructionModel::query()->create([
        'server' => 'echo',
        'tools' => ['echo-mcp-tool', 'renamed-echo'],
    ]);

    expect($instruction->fresh()?->tools)->toBe(['echo-mcp-tool', 'renamed-echo'])
        ->and(McpInstructionModel::query()->create(['server' => 'plain'])->fresh()?->tools)->toBeNull();
});

it('stores an empty tool list apart from no list', function (): void {
    $instruction = McpInstructionModel::query()->create(['server' => 'echo', 'tools' => []]);

    expect($instruction->fresh()?->tools)->toBe([])
        ->and(DB::table(Tables::name('mcp_instructions'))->where('server', 'echo')->value('tools'))->toBe('[]');
});
