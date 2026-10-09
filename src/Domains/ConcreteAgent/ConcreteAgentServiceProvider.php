<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent;

use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\ConcreteAgentOverrides;
use RefactorCircus\Foundation\Support\ServiceProvider;

class ConcreteAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentRegistry::class);

        $this->app->scoped(ConcreteAgentOverrides::class);
    }

    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
