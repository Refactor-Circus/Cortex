<?php

declare(strict_types=1);

namespace JayI\Cortex\Atrium;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use JayI\Atrium\Navigation\NavItem;
use JayI\Atrium\Plugins\Plugin;
use JayI\Atrium\Search\SearchResult;
use JayI\Atrium\Search\SearchSource;
use JayI\Atrium\Settings\SettingsPanel;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Http\Ui\ConcreteAgentUiController;
use JayI\Cortex\Http\Ui\McpInstructionUiController;
use JayI\Cortex\Http\Ui\RunAgentUiController;
use JayI\Cortex\Http\Ui\ServerUiController;
use JayI\Cortex\Http\Ui\ToolDescriptionUiController;
use JayI\Cortex\Http\Ui\ToolUiController;
use JayI\Cortex\Http\Ui\VirtualAgentUiController;
use JayI\Cortex\Http\Ui\VirtualAgentVersionUiController;
use JayI\Cortex\Mcp\McpServerRegistry;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Tools\ToolRegistry;

/**
 * Registers Cortex inside the Atrium dashboard.
 *
 * Routes are declared here rather than in a route file so they land inside
 * Atrium's group, inheriting its prefix, middleware and authorization gate.
 */
class CortexPlugin extends Plugin
{
    public function key(): string
    {
        return 'cortex';
    }

    public function label(): string
    {
        return 'Cortex';
    }

    public function navigation(): array
    {
        return [
            NavItem::make(__('cortex::cortex.virtual_agents'))->route('atrium.cortex.virtual-agents.index')->group('Cortex')->sort(10),
            NavItem::make(__('cortex::cortex.concrete_agents'))->route('atrium.cortex.concrete-agents.index')->group('Cortex')->sort(20),
            NavItem::make(__('cortex::cortex.run_agent'))->route('atrium.cortex.run')->group('Cortex')->sort(30),
            NavItem::make(__('cortex::cortex.tools'))->route('atrium.cortex.tools.index')->group('Cortex')->sort(40),
            NavItem::make(__('cortex::cortex.servers'))->route('atrium.cortex.servers.index')->group('Cortex')->sort(50),
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

    public function search(): ?SearchSource
    {
        return SearchSource::make('cortex')
            ->label(__('cortex::cortex.label'))
            ->using(function (string $query): array {
                $agents = VirtualAgent::query()
                    ->where(fn (Builder $builder): Builder => $builder->where('name', 'like', '%'.$query.'%')->orWhere('slug', 'like', '%'.$query.'%'))
                    ->limit(5)
                    ->get()
                    ->map(fn (VirtualAgent $agent): SearchResult => SearchResult::make(
                        $agent->name,
                        route('atrium.cortex.virtual-agents.edit', $agent->slug),
                    )->subtitle($agent->slug)->group(__('cortex::cortex.virtual_agents')))
                    ->all();

                $concrete = collect(app(AgentRegistry::class)->names())
                    ->filter(fn (string $name): bool => str_contains(strtolower($name), strtolower($query)))
                    ->take(5)
                    ->map(fn (string $name): SearchResult => SearchResult::make(
                        $name,
                        route('atrium.cortex.concrete-agents.show', $name),
                    )->group(__('cortex::cortex.concrete_agents')))
                    ->values()
                    ->all();

                return [...$agents, ...$concrete];
            });
    }
}
