<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavGroup;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Plugins\Support\Plugin;
use RefactorCircus\Atrium\Domains\Search\Data\SearchResult;
use RefactorCircus\Atrium\Domains\Search\Data\SearchSource;
use RefactorCircus\Atrium\Domains\Settings\Data\SettingsPanel;
use RefactorCircus\Atrium\Support\Icons;
use RefactorCircus\Cortex\Atrium\Http\Controllers\ConcreteAgentUiController;
use RefactorCircus\Cortex\Atrium\Http\Controllers\McpInstructionUiController;
use RefactorCircus\Cortex\Atrium\Http\Controllers\RedirectDomainUiController;
use RefactorCircus\Cortex\Atrium\Http\Controllers\RunAgentUiController;
use RefactorCircus\Cortex\Atrium\Http\Controllers\ServerUiController;
use RefactorCircus\Cortex\Atrium\Http\Controllers\ToolDescriptionUiController;
use RefactorCircus\Cortex\Atrium\Http\Controllers\ToolUiController;
use RefactorCircus\Cortex\Atrium\Http\Controllers\VirtualAgentUiController;
use RefactorCircus\Cortex\Atrium\Http\Controllers\VirtualAgentVersionUiController;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

/**
 * Registers Cortex inside the Atrium dashboard.
 *
 * Routes are declared here rather than in a route file so they land inside
 * Atrium's group, inheriting its prefix, middleware and authorization gate.
 */
class CortexPlugin extends Plugin
{
    /**
     * Features from `cortex.atrium.features` that switch Cortex in Atrium on
     * and off as a whole. A feature class that cannot be loaded, such as
     * CortexSupportFeature without refactor-circus/pennantplus, is skipped.
     *
     * @return array<int, string>
     */
    public function features(): array
    {
        return $this->featuresFromConfig('cortex.atrium.features');
    }

    /**
     * The package's section in the sidebar rail: its icon and its place.
     */
    public function navigationGroups(): array
    {
        return [
            NavGroup::make('Cortex')->icon(Icons::svg('cpu-chip'))->sort(50),
        ];
    }

    /**
     * Each item is shown when the policy check behind its page passes, asked
     * as the JSON API asks it. Tools and servers are listed by the API
     * without a policy check, so they are shown to everyone in Atrium.
     */
    public function navigation(): array
    {
        return [
            NavItem::make(__('cortex::cortex.virtual_agents'))
                ->icon(Icons::svg('sparkles'))
                ->route('atrium.cortex.virtual-agents.index')
                ->group('Cortex')
                ->sort(10)
                ->authorize(fn (Request $request): bool => ScreenAccess::allowsFor($request->user(), 'viewAny', VirtualAgentModel::class)),

            NavItem::make(__('cortex::cortex.concrete_agents'))
                ->icon(Icons::svg('cpu-chip'))
                ->route('atrium.cortex.concrete-agents.index')
                ->group('Cortex')
                ->sort(20)
                ->authorize(fn (Request $request): bool => ScreenAccess::allowsFor($request->user(), 'viewAny', ConcreteAgentOverrideModel::class)),

            NavItem::make(__('cortex::cortex.run_agent'))
                ->icon(Icons::svg('play'))
                ->route('atrium.cortex.run')
                ->group('Cortex')
                ->sort(30)
                ->authorize(fn (Request $request): bool => self::mayViewAgents($request->user())),

            NavItem::make(__('cortex::cortex.tools'))
                ->icon(Icons::svg('wrench-screwdriver'))
                ->route('atrium.cortex.tools.index')
                ->group('Cortex')
                ->sort(40),

            NavItem::make(__('cortex::cortex.servers'))
                ->icon(Icons::svg('server-stack'))
                ->route('atrium.cortex.servers.index')
                ->group('Cortex')
                ->sort(50),

            NavItem::make(__('cortex::cortex.redirect_domains'))
                ->icon(Icons::svg('globe-alt'))
                ->route('atrium.cortex.redirect-domains.index')
                ->group('Cortex')
                ->sort(60)
                ->authorize(fn (Request $request): bool => ScreenAccess::allowsFor($request->user(), 'viewAny', RedirectDomainModel::class)),

            // The package's own audit log, while an audit log is installed.
            $this->historyNavItem('cortex')->group('Cortex')->sort(90),
        ];
    }

    public function routes(): void
    {
        Route::name('cortex.')->group(function (): void {
            Route::get('cortex/virtual-agents', [VirtualAgentUiController::class, 'index'])->name('virtual-agents.index');
            Route::get('cortex/virtual-agents/create', [VirtualAgentUiController::class, 'create'])->name('virtual-agents.create');
            Route::post('cortex/virtual-agents', [VirtualAgentUiController::class, 'store'])->name('virtual-agents.store');
            Route::get('cortex/virtual-agents/{agent:slug}/edit', [VirtualAgentUiController::class, 'edit'])->name('virtual-agents.edit');
            Route::put('cortex/virtual-agents/{agent:slug}', [VirtualAgentUiController::class, 'update'])->name('virtual-agents.update');
            Route::delete('cortex/virtual-agents/{agent:slug}', [VirtualAgentUiController::class, 'destroy'])->name('virtual-agents.destroy');
            Route::post('cortex/virtual-agents/{agent:slug}/versions/{version}/publish', [VirtualAgentVersionUiController::class, 'publish'])
                ->whereNumber('version')->name('virtual-agents.versions.publish');

            Route::get('cortex/concrete-agents', [ConcreteAgentUiController::class, 'index'])->name('concrete-agents.index');
            Route::get('cortex/concrete-agents/{agent}', [ConcreteAgentUiController::class, 'show'])->name('concrete-agents.show');
            Route::post('cortex/concrete-agents/{agent}/versions', [ConcreteAgentUiController::class, 'store'])->name('concrete-agents.store');
            Route::post('cortex/concrete-agents/{agent}/versions/{version}/publish', [ConcreteAgentUiController::class, 'publish'])
                ->whereNumber('version')->name('concrete-agents.publish');
            Route::put('cortex/concrete-agents/{agent}/tools', [ConcreteAgentUiController::class, 'tools'])->name('concrete-agents.tools');
            Route::delete('cortex/concrete-agents/{agent}/override', [ConcreteAgentUiController::class, 'destroy'])->name('concrete-agents.destroy');

            Route::get('cortex/run', [RunAgentUiController::class, 'create'])->name('run');
            Route::post('cortex/run', [RunAgentUiController::class, 'store'])->name('run.store');

            Route::get('cortex/tools', [ToolUiController::class, 'index'])->name('tools.index');
            Route::get('cortex/tools/{tool}/description', [ToolDescriptionUiController::class, 'show'])->name('tools.description');
            Route::post('cortex/tools/{tool}/description/versions', [ToolDescriptionUiController::class, 'store'])->name('tools.description.store');
            Route::post('cortex/tools/{tool}/description/versions/{version}/publish', [ToolDescriptionUiController::class, 'publish'])
                ->whereNumber('version')->name('tools.description.publish');
            Route::delete('cortex/tools/{tool}/description', [ToolDescriptionUiController::class, 'destroy'])->name('tools.description.destroy');

            Route::get('cortex/servers', [ServerUiController::class, 'index'])->name('servers.index');
            Route::get('cortex/servers/{server}/instructions', [McpInstructionUiController::class, 'show'])->name('servers.instructions');
            Route::post('cortex/servers/{server}/instructions/versions', [McpInstructionUiController::class, 'store'])->name('servers.instructions.store');
            Route::post('cortex/servers/{server}/instructions/versions/{version}/publish', [McpInstructionUiController::class, 'publish'])
                ->whereNumber('version')->name('servers.instructions.publish');
            Route::delete('cortex/servers/{server}/instructions', [McpInstructionUiController::class, 'destroy'])->name('servers.instructions.destroy');

            Route::get('cortex/redirect-domains', [RedirectDomainUiController::class, 'index'])->name('redirect-domains.index');
            Route::post('cortex/redirect-domains', [RedirectDomainUiController::class, 'store'])->name('redirect-domains.store');
            Route::delete('cortex/redirect-domains/{domain}', [RedirectDomainUiController::class, 'destroy'])->name('redirect-domains.destroy');
        });
    }

    public function settings(): ?SettingsPanel
    {
        return SettingsPanel::make('cortex')
            ->label(__('cortex::cortex.settings_label'))
            ->description(__('cortex::cortex.settings_description'))
            ->view('cortex::ui.settings')
            ->resolve(fn (): array => [
                'providers' => (array) config('cortex.providers', []),
                'toolCount' => count(app(ToolRegistry::class)->names()),
                'agentCount' => count(app(AgentRegistry::class)->names()),
                'serverCount' => count(app(McpServerRegistry::class)->all()),
                'cacheEnabled' => (bool) config('cortex.cache.enabled', true),
            ]);
    }

    /**
     * Virtual agents and concrete agents, each only to those who may list
     * them, and each result only when its page would open.
     */
    public function search(): ?SearchSource
    {
        return SearchSource::make('cortex')
            ->label(__('cortex::cortex.label'))
            ->authorize(static fn (Request $request): bool => ScreenAccess::allowsFor($request->user(), 'viewAny', VirtualAgentModel::class)
                || ScreenAccess::allowsFor($request->user(), 'viewAny', ConcreteAgentOverrideModel::class))
            ->using(static function (string $query): array {
                $user = auth()->user();

                $agents = ! ScreenAccess::allowsFor($user, 'viewAny', VirtualAgentModel::class) ? [] : VirtualAgentModel::query()
                    ->where(fn (Builder $builder): Builder => $builder->where('name', 'like', '%'.$query.'%')->orWhere('slug', 'like', '%'.$query.'%'))
                    ->limit(5)
                    ->get()
                    ->filter(fn (VirtualAgentModel $agent): bool => ScreenAccess::allowsFor($user, 'view', $agent))
                    ->map(fn (VirtualAgentModel $agent): SearchResult => SearchResult::make(
                        $agent->name,
                        route('atrium.cortex.virtual-agents.edit', $agent->slug),
                    )->subtitle($agent->slug)->group(__('cortex::cortex.virtual_agents')))
                    ->values()
                    ->all();

                $concrete = ! ScreenAccess::allowsFor($user, 'viewAny', ConcreteAgentOverrideModel::class) ? [] : collect(app(AgentRegistry::class)->names())
                    ->filter(fn (string $name): bool => str_contains(strtolower($name), strtolower($query)))
                    ->take(5)
                    ->filter(fn (string $name): bool => ScreenAccess::allowsFor($user, 'view', ConcreteAgentOverrideModel::query()->firstOrNew(['agent' => $name])))
                    ->map(fn (string $name): SearchResult => SearchResult::make(
                        $name,
                        route('atrium.cortex.concrete-agents.show', $name),
                    )->group(__('cortex::cortex.concrete_agents')))
                    ->values()
                    ->all();

                return [...$agents, ...$concrete];
            });
    }

    /**
     * Whether a user may see either kind of agent: enough to open the run
     * page, which then offers only the agents they may run (possibly none).
     * Asked of the classes, so building the navigation runs no queries.
     */
    public static function mayViewAgents(mixed $user): bool
    {
        return ScreenAccess::allowsFor($user, 'viewAny', VirtualAgentModel::class)
            || ScreenAccess::allowsFor($user, 'viewAny', ConcreteAgentOverrideModel::class);
    }
}
