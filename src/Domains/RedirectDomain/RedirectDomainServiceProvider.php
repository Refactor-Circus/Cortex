<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain;

use JayI\Cortex\Domains\RedirectDomain\Http\Controllers\OAuthRegisterController;
use JayI\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use JayI\Foundation\Support\ServiceProvider;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController as BaseOAuthRegisterController;

class RedirectDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedirectDomains::class);

        // Mcp::oauthRoutes() points dynamic client registration at the base
        // controller; the router resolves it through the container.
        $this->app->bind(BaseOAuthRegisterController::class, OAuthRegisterController::class);
    }

    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
