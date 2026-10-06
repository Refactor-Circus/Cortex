<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent;

use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\ConcreteAgent\Services\ConcreteAgentOverrides;
use JayI\Foundation\Support\ServiceProvider;

class ConcreteAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentRegistry::class);

        $this->app->scoped(ConcreteAgentOverrides::class);
    }

    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Cortex\Models\ConcreteAgentOverride' => ConcreteAgentOverrideModel::class,
            'JayI\Cortex\Models\ConcreteAgentOverrideVersion' => ConcreteAgentOverrideVersionModel::class,
        ]);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
