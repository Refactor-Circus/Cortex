<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests;

use RefactorCircus\Atrium\AtriumServiceProvider;
use RefactorCircus\Cortex\CortexServiceProvider;
use Laravel\Ai\AiServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AiServiceProvider::class,
            McpServiceProvider::class,
            AtriumServiceProvider::class,
            CortexServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        $app['config']->set('cache.default', 'array');

        // Pin the publication cache to the per-test array store — the
        // availability probe would otherwise find a running local Redis and
        // leak cached publications across tests.
        $app['config']->set('cortex.cache.store', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
    }
}
