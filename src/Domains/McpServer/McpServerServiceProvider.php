<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer;

use RefactorCircus\Cortex\Domains\McpServer\Services\McpInstructionOverrides;
use RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry;
use RefactorCircus\Keystone\Support\ServiceProvider;

class McpServerServiceProvider extends ServiceProvider
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
