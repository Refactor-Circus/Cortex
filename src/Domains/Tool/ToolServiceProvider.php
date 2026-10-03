<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool;

use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use JayI\Cortex\Domains\Tool\Services\ToolDescriptionOverrides;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Support\ServiceProvider;

class ToolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class);

        $this->app->scoped(ToolDescriptionOverrides::class);
    }

    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Cortex\Models\ToolDescription' => ToolDescriptionModel::class,
            'JayI\Cortex\Models\ToolDescriptionVersion' => ToolDescriptionVersionModel::class,
        ]);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
