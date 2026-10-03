<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains;

use Illuminate\Support\ServiceProvider;
use JayI\Cortex\Domains\ConcreteAgent\ConcreteAgentServiceProvider;
use JayI\Cortex\Domains\McpServer\McpServerServiceProvider;
use JayI\Cortex\Domains\Tool\ToolServiceProvider;
use JayI\Cortex\Domains\VirtualAgent\VirtualAgentServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * The domain service providers.
     *
     * @var array<int, class-string<ServiceProvider>>
     */
    private array $providers = [
        ConcreteAgentServiceProvider::class,
        McpServerServiceProvider::class,
        ToolServiceProvider::class,
        VirtualAgentServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }
}
