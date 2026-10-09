<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain;

use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController as BaseOAuthRegisterController;
use RefactorCircus\Cortex\Domains\RedirectDomain\Http\Controllers\OAuthRegisterController;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use RefactorCircus\Keystone\Support\ServiceProvider;

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
