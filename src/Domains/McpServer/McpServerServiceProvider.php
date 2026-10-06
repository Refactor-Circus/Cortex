<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer;

use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use JayI\Cortex\Domains\McpServer\Services\McpInstructionOverrides;
use JayI\Cortex\Domains\McpServer\Services\McpServerRegistry;
use JayI\Foundation\Support\ServiceProvider;

class McpServerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(McpServerRegistry::class);

        $this->app->scoped(McpInstructionOverrides::class);
    }

    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Cortex\Models\McpInstruction' => McpInstructionModel::class,
            'JayI\Cortex\Models\McpInstructionVersion' => McpInstructionVersionModel::class,
        ]);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
