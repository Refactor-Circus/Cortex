<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Laravel\Mcp\Request as McpRequest;
use RefactorCircus\Cortex\Atrium\CortexPlugin;
use RefactorCircus\Cortex\Atrium\ScreenAccess;
use RefactorCircus\Cortex\Domains\DomainServiceProvider;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Foundation\Packages\Package;
use RefactorCircus\Foundation\Support\PackageServiceProvider;

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
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        $this->registerAtriumPlugin(CortexPlugin::class);

        $this->registerMcpServer();

        $this->loadHistoryRoutes();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cortex');

        // @cortexCan('update', $agent) ... @endcortexCan: the screens' own
        // policy check, so a control shows only when its action is allowed.
        Blade::if('cortexCan', ScreenAccess::allows(...));

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'cortex');

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
