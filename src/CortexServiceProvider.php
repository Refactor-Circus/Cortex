<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Laravel\Mcp\Request as McpRequest;
use RefactorCircus\Cortex\Atrium\CortexPlugin;
use RefactorCircus\Cortex\Atrium\ScreenAccess;
use RefactorCircus\Cortex\Atrium\ScreenRegistrations;
use RefactorCircus\Cortex\Domains\DomainServiceProvider;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Support\PackageServiceProvider;

class CortexServiceProvider extends PackageServiceProvider
{
    /**
     * Describe Cortex to the shared runtime.
     *
     * Cortex does not connect its own server to itself through
     * `registerCortex()`: it registers its server with its own registry, and
     * its management tools are not offered to agents.
     */
    protected function definition(): Package
    {
        return Package::make('cortex', __NAMESPACE__)
            ->label('Cortex')
            ->server(CortexServer::class);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cortex.php', 'cortex');

        $this->keepRoutesEnabled();

        $this->registerPackage();

        $this->app->register(DomainServiceProvider::class);

        $this->app->singleton(Cortex::class);

        $this->fillMcpRequestsForAgents();
    }

    /**
     * The shared runtime loads the JSON API only while `cortex.routes.enabled`
     * is true. A config file published before that key existed replaces the
     * whole `routes` array, so default it to on rather than drop the API.
     */
    private function keepRoutesEnabled(): void
    {
        $config = $this->config();

        if ($config->get('cortex.routes.enabled') === null) {
            $config->set('cortex.routes.enabled', true);
        }
    }

    /**
     * Give MCP tools their arguments when an agent calls them.
     *
     * An MCP server hands a tool call's arguments to the tool's request
     * through the `mcp.request` binding, and laravel/mcp copies them into any
     * request subclass the tool type-hints. laravel/ai's McpServerTool, which
     * wraps every MCP tool an agent uses, binds them as the base
     * Laravel\Mcp\Request instead — so a tool that type-hints its own
     * request class would receive none. Copy them across in that case.
     */
    private function fillMcpRequestsForAgents(): void
    {
        $this->app->resolving(McpRequest::class, function (McpRequest $request, Application $app): void {
            if ($app->bound('mcp.request') || ! $app->bound(McpRequest::class)) {
                return;
            }

            $arguments = $app->make(McpRequest::class);

            if ($arguments !== $request) {
                $request->setArguments($arguments->all());
                $request->setMeta($arguments->meta());
            }
        });
    }

    /**
     * Put the redirect-domain UI on other packages' screens, as
     * `cortex.atrium.redirect_domains.screens` lists them. Behind the same
     * guard as registerAtriumPlugin(): Atrium is suggested, not required, so
     * its class is named only as a string, and the dashboard UI must be on.
     */
    private function registerAtriumScreens(): void
    {
        if (! class_exists('RefactorCircus\\Atrium\\Atrium') || $this->config()->get('cortex.ui.enabled') !== true) {
            return;
        }

        ScreenRegistrations::register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Policy keys still naming the models by their names from before the
        // ConcreteServer rename were already moved to the new classes by
        // ConcreteServerServiceProvider::register(), which runs before any
        // provider boots.
        $this->registerPolicies();

        // Missing records on Cortex's routes answer a plain "Not found.", as its MCP tools do.
        $this->registerNotFoundResponses();

        // Before the Atrium registrations, whose labels are translated.
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'cortex');

        $this->registerAtriumPlugin(CortexPlugin::class);

        $this->registerAtriumScreens();

        $this->registerMcpServer();

        $this->loadHistoryRoutes();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cortex');

        // @cortexCan('update', $agent) ... @endcortexCan: the screens' own
        // policy check, so a control shows only when its action is allowed.
        Blade::if('cortexCan', ScreenAccess::allows(...));

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/cortex.php' => config_path('cortex.php'),
        ], ['cortex', 'cortex-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/cortex'),
        ], ['cortex', 'cortex-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/cortex'),
        ], ['cortex', 'cortex-lang']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['cortex', 'cortex-migrations']);
    }
}
