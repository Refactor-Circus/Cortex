<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Support;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

/**
 * Runs a test file under `DatabaseMigrations` on a file-based SQLite
 * database (D-023): no transaction wraps the test, so `DB::afterCommit()`
 * waits for the action's own commit and a unique-index collision behaves as
 * it does in production. Each test gets its own database file, removed when
 * the application is destroyed.
 *
 * Use it with `uses(FileDatabaseMigrations::class)` in the test file.
 */
trait FileDatabaseMigrations
{
    use DatabaseMigrations {
        runDatabaseMigrations as runLaravelDatabaseMigrations;
    }

    private ?string $databaseFile = null;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $this->databaseFile = (string) tempnam(sys_get_temp_dir(), 'cortex-test-db-');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => $this->databaseFile,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    /**
     * The package migrations run through `migrate:fresh` (and roll back
     * through `migrate:rollback`) instead of Testbench's loader.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->app['migrator']->path(dirname(__DIR__, 2).'/database/migrations');
    }

    public function runDatabaseMigrations(): void
    {
        $file = $this->databaseFile;

        // Testbench runs these callbacks last registered first, so this one,
        // registered before the rollback, runs once the tables are gone.
        $this->beforeApplicationDestroyed(function () use ($file): void {
            DB::disconnect();

            if ($file !== null && is_file($file)) {
                unlink($file);
            }
        });

        $this->runLaravelDatabaseMigrations();
    }
}
