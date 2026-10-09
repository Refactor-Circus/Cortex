<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains;

use Illuminate\Support\ServiceProvider;
use RefactorCircus\Cortex\Domains\ConcreteAgent\ConcreteAgentServiceProvider;
use RefactorCircus\Cortex\Domains\McpServer\McpServerServiceProvider;
use RefactorCircus\Cortex\Domains\RedirectDomain\RedirectDomainServiceProvider;
use RefactorCircus\Cortex\Domains\Tool\ToolServiceProvider;
use RefactorCircus\Cortex\Domains\VirtualAgent\VirtualAgentServiceProvider;

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
        RedirectDomainServiceProvider::class,
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
