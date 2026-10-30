<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\WithoutAtrium;

use Laravel\Ai\AiServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Cortex\CortexServiceProvider;

/**
 * Cortex booted as an application without Atrium boots it: no Atrium
 * provider, and (through tests/WithoutAtrium/bootstrap.php) no Atrium class
 * can load. It names no Atrium class, so it never needs one.
 */
abstract class WithoutAtriumTestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AiServiceProvider::class,
            McpServiceProvider::class,
            CortexServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cortex.cache.store', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');
    }
}
