<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent;

use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Foundation\Support\ServiceProvider;

class VirtualAgentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Cortex\Models\VirtualAgent' => VirtualAgentModel::class,
            'JayI\Cortex\Models\VirtualAgentVersion' => VirtualAgentVersionModel::class,
        ]);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
