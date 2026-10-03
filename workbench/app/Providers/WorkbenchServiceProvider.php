<?php

namespace Workbench\App\Providers;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Ai\Agents\CatalogAgent;
use Workbench\App\Ai\Agents\RefundAgent;
use Workbench\App\Ai\Agents\SummarizerAgent;
use Workbench\App\Ai\Agents\SupportTriageAgent;
use Workbench\App\Ai\Tools\CurrentTimeTool;
use Workbench\App\Domains\Catalog\Tools\CheckInventoryTool;
use Workbench\App\Domains\Orders\Tools\LookupOrderTool;
use Workbench\App\Domains\Orders\Tools\RefundOrderTool;
use Workbench\App\Domains\Support\Tools\CreateTicketTool;
use Workbench\App\Domains\Support\Tools\SearchKnowledgeBaseTool;
use Workbench\App\Http\Middleware\SignInWorkbenchUser;
use Workbench\App\Mcp\Servers\CatalogServer;
use Workbench\App\Mcp\Servers\SupportServer;
use Workbench\App\Models\User;

/**
 * Turns the workbench into a demo app: the tools, agents and MCP servers an
 * application would register with Cortex, configured the way it would.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        config([
            'auth.providers.users.model' => User::class,

            // Offered when configuring a virtual agent. Nothing calls them
            // unless an agent is run from the dashboard.
            'cortex.providers' => [
                'anthropic' => ['claude-sonnet-5-5', 'claude-opus-5-5', 'claude-haiku-4-5'],
                'openai' => ['gpt-5', 'gpt-5-mini'],
            ],

            // Tools under Workbench\App\Domains\{tag}\ are tagged by domain.
            'cortex.tool_tags.namespaces' => [
                'App\\Domains\\{tag}\\',
                'Workbench\\App\\Domains\\{tag}\\',
            ],

            'cortex.tools' => [
                'lookup-order' => LookupOrderTool::class,
                'refund-order' => ['class' => RefundOrderTool::class, 'tags' => ['billing']],
                'search-knowledge-base' => SearchKnowledgeBaseTool::class,
                'create-ticket' => CreateTicketTool::class,
                'check-inventory' => CheckInventoryTool::class,
                'current-time' => ['class' => CurrentTimeTool::class, 'tags' => ['utility']],
            ],

            'cortex.agents' => [
                'support-triage' => SupportTriageAgent::class,
                'refund-agent' => RefundAgent::class,
                'catalog-agent' => CatalogAgent::class,
                'summarizer' => SummarizerAgent::class,
            ],

            'cortex.mcp.servers' => [
                'support' => SupportServer::class,
                'catalog' => CatalogServer::class,
            ],

            // The database is rebuilt on every serve, so cache publications
            // there rather than in a Redis that would outlive it.
            'cortex.cache.store' => 'database',
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Keep the workbench user signed in whatever URL is opened first.
        $this->callAfterResolving(HttpKernel::class, function (HttpKernel $kernel): void {
            if ($kernel instanceof Kernel) {
                $kernel->appendMiddlewareToGroup('web', SignInWorkbenchUser::class);
            }
        });

        // A real application defines a real gate; the demo user may see it all.
        Gate::define('viewAtrium', fn ($user = null): bool => true);
    }
}
