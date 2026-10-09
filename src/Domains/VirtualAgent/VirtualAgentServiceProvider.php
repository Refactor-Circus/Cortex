<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent;

use RefactorCircus\Foundation\Support\ServiceProvider;

class VirtualAgentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
