<?php

declare(strict_types=1);

namespace JayI\Cortex;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use JayI\Atrium\Facades\Atrium;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Agents\ConcreteAgentOverrides;
use JayI\Cortex\Atrium\CortexPlugin;
use JayI\Cortex\Http\Ui\ScreenAccess;
use JayI\Cortex\Mcp\CortexServer;
use JayI\Cortex\Mcp\McpInstructionOverrides;
use JayI\Cortex\Mcp\McpServerRegistry;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Request as McpRequest;

class CortexServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cortex.php', 'cortex');

        $this->app->singleton(Tools\ToolRegistry::class);

        $this->app->scoped(Tools\ToolDescriptionOverrides::class);

        $this->app->singleton(McpServerRegistry::class);

        $this->app->scoped(McpInstructionOverrides::class);

        $this->app->singleton(AgentRegistry::class);

        $this->app->scoped(ConcreteAgentOverrides::class);

        $this->app->singleton(Cortex::class);

        $this->fillMcpRequestsForAgents();
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

        $this->loadRoutesFrom(__DIR__.'/../routes/cortex.php');

        $this->registerAtriumPlugin();

        $this->registerMcpServers();

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

    /**
     * Register the policy for each model from `cortex.policies`, so the JSON
     * API and MCP tools, and the application's own `can()` checks, share them.
     */
    private function registerPolicies(): void
    {
        /** @var array<class-string, class-string> $policies */
        $policies = $this->app->make('config')->get('cortex.policies', []);

        foreach ($policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Register Cortex with the Atrium dashboard.
     *
     * Atrium discovers the plugin from composer.json, so this only needs to
     * honour the config switch that turns the dashboard surface off.
     */
    private function registerAtriumPlugin(): void
    {
        if ($this->app->make('config')->get('cortex.ui.enabled') !== true) {
            return;
        }

        Atrium::plugin(CortexPlugin::class);
    }

    /**
     * Register the Cortex MCP server transports enabled in the config.
     */
    private function registerMcpServers(): void
    {
        $config = $this->app->make('config');

        if ($config->get('cortex.mcp.web.enabled') === true) {
            /** @var array<int, string> $middleware */
            $middleware = $config->get('cortex.mcp.web.middleware', []);

            Mcp::web((string) $config->get('cortex.mcp.web.route'), CortexServer::class)
                ->middleware($middleware);
        }

        if ($config->get('cortex.mcp.local.enabled') === true) {
            Mcp::local((string) $config->get('cortex.mcp.local.handle'), CortexServer::class);
        }
    }
}
