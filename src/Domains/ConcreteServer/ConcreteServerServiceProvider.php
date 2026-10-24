<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer;

use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpInstructionOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Keystone\Support\ServiceProvider;

class ConcreteServerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(McpServerRegistry::class);

        $this->app->scoped(McpInstructionOverrides::class);
    }

    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
