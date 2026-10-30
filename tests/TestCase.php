<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Laravel\Ai\AiServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Atrium\AtriumServiceProvider;
use RefactorCircus\Cortex\CortexServiceProvider;
use Workbench\App\Http\Controllers\TestHostScreenController;
use Workbench\App\Models\Organization;
use Workbench\App\Models\User;
use Workbench\App\Policies\OrganizationPolicy;
use Workbench\App\Policies\UserPolicy;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // The host's own owner policies, which the bundled
        // RedirectDomainPolicy asks `update` of for owned domains.
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }

    protected function tearDown(): void
    {
        OrganizationPolicy::$deniesUpdate = false;
        UserPolicy::$deniesUpdate = false;

        parent::tearDown();
    }

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

    /**
     * The workbench test host: another package's screen, which Cortex
     * extends in its tests without depending on that package.
     *
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        View::addNamespace('workbench', dirname(__DIR__).'/workbench/resources/views');

        $router->middleware('web')
            ->get('test-host/{key}/{id?}', TestHostScreenController::class)
            ->name('test-host');
    }

    protected function defineDatabaseMigrations(): void
    {
        // The users table, for the workbench User the test host shows.
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__).'/workbench/database/migrations');
    }
}
