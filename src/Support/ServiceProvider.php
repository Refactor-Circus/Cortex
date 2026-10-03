<?php

declare(strict_types=1);

namespace JayI\Cortex\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Base class for the Cortex domain service providers.
 *
 * Every JSON API route shares one group - the configured prefix and
 * middleware, and the `cortex.` name prefix - so each domain loads its routes
 * file through `loadApiRoutesFrom()` rather than building the group itself.
 */
abstract class ServiceProvider extends BaseServiceProvider
{
    /**
     * Load a routes file inside the JSON API's route group.
     */
    protected function loadApiRoutesFrom(string $path): void
    {
        if ($this->routesAreCached()) {
            return;
        }

        $config = $this->app->make(Repository::class);

        /** @var string $prefix */
        $prefix = $config->get('cortex.routes.prefix');

        /** @var array<int, string> $middleware */
        $middleware = $config->get('cortex.routes.middleware');

        Route::prefix($prefix)->middleware($middleware)->name('cortex.')->group($path);
    }

    /**
     * Keep the class names models were stored under before they moved into
     * their domain, so any polymorphic `*_type` column, audit trail or other
     * record an application wrote with the old names still resolves - and new
     * records keep writing the same value.
     *
     * @param  array<string, class-string<Model>>  $map
     */
    protected function keepMorphAliases(array $map): void
    {
        Relation::morphMap($map);
    }

    protected function routesAreCached(): bool
    {
        return $this->app instanceof CachesRoutes && $this->app->routesAreCached();
    }
}
