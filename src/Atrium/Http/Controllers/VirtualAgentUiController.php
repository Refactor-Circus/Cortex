<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Cortex\Atrium\Screens;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\DeleteVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListProvidersAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\UpdateVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\CreateVirtualAgentData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\ListVirtualAgentsData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\UpdateVirtualAgentData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class VirtualAgentUiController
{
    use AuthorizesScreens;

    public function index(Request $request, ScreenExtensions $extensions): View
    {
        $this->authorizeScreen('viewAny', VirtualAgentModel::class);

        /** @var view-string $view */
        $view = 'cortex::ui.virtual-agents.index';

        $filters = ListVirtualAgentsData::from($request->query());

        return view($view, [
            // The list shows each agent's version number only.
            'agents' => app(ListVirtualAgentsAction::class)->execute(new ListVirtualAgentsData(
                perPage: $filters->perPage,
                page: $filters->page,
                summary: true,
            )),
            'resolved' => $extensions->resolve(Screens::declare('cortex.virtual-agents', Screen::LIST, null, VirtualAgentModel::class), $request),
        ]);
    }

    public function create(Request $request, ScreenExtensions $extensions): View
    {
        $this->authorizeScreen('create', VirtualAgentModel::class);

        /** @var view-string $view */
        $view = 'cortex::ui.virtual-agents.form';

        return view($view, [
            ...$this->formData(),
            'agent' => null,
            'resolved' => $extensions->resolve(Screens::declare('cortex.virtual-agent-create', Screen::DETAIL, null, VirtualAgentModel::class), $request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', VirtualAgentModel::class);

        app(CreateVirtualAgentAction::class)->execute(CreateVirtualAgentData::from($this->input($request)));

        return redirect()
            ->route('atrium.cortex.virtual-agents.index')
            ->with('status', __('cortex::cortex.agent_created'));
    }

    public function edit(Request $request, ScreenExtensions $extensions, VirtualAgentModel $agent): View
    {
        $this->authorizeScreen('view', $agent);

        /** @var view-string $view */
        $view = 'cortex::ui.virtual-agents.form';

        return view($view, [
            ...$this->formData($agent),
            'agent' => $agent->load(['publishedVersion', 'subAgents']),
            'versions' => $agent->versions()->chaperone('virtualAgent')->orderByDesc('version')->get(),
            'resolved' => $extensions->resolve(Screens::declare('cortex.virtual-agent', Screen::DETAIL, $agent, null), $request),
        ]);
    }

    public function update(Request $request, VirtualAgentModel $agent): RedirectResponse
    {
        $this->authorizeScreen('update', $agent);

        app(UpdateVirtualAgentAction::class)->execute($agent, UpdateVirtualAgentData::from($this->input($request)));

        return redirect()
            ->route('atrium.cortex.virtual-agents.edit', $agent->slug)
            ->with('status', __('cortex::cortex.agent_updated'));
    }

    public function destroy(VirtualAgentModel $agent): RedirectResponse
    {
        $this->authorizeScreen('delete', $agent);

        app(DeleteVirtualAgentAction::class)->execute($agent);

        return redirect()
            ->route('atrium.cortex.virtual-agents.index')
            ->with('status', __('cortex::cortex.agent_deleted'));
    }

    /**
     * Options the agent form needs.
     *
     * A provider or model saved earlier stays selectable even when it is no
     * longer offered, so editing an agent never silently rewrites it.
     *
     * @return array<string, mixed>
     */
    private function formData(?VirtualAgentModel $agent = null): array
    {
        $providers = app(ListProvidersAction::class)->execute();

        $names = array_map(fn (array $provider): string => (string) $provider['name'], $providers);

        if ($agent?->provider !== null && ! in_array($agent->provider, $names, true)) {
            array_unshift($names, $agent->provider);
        }

        return [
            'providers' => $providers,
            'providerNames' => $names,
            'tools' => app(ToolRegistry::class)->summaries(),
            'concreteAgents' => app(AgentRegistry::class)->names(),
            'agents' => VirtualAgentModel::query()
                ->when($agent !== null, fn (Builder $query): Builder => $query->whereKeyNot($agent?->getKey()))
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'selectedSubAgents' => $agent?->subAgents->pluck('slug')->all() ?? [],
        ];
    }

    /**
     * The form input, with blank settings dropped, ready to validate.
     *
     * An HTML form submits every number input, so an untouched field arrives
     * as an empty string. Those are absent values, not invalid ones, and must
     * be removed before the rules run rather than after.
     *
     * @return array<string, mixed>
     */
    private function input(Request $request): array
    {
        $settings = array_filter(
            (array) $request->input('settings', []),
            fn (mixed $value): bool => $value !== null && $value !== '',
        );

        // Form input arrives as strings; store real numbers so the JSON API
        // and the dashboard write identical rows.
        $settings = array_map(
            fn (mixed $value): mixed => is_numeric($value)
                ? (str_contains((string) $value, '.') ? (float) $value : (int) $value)
                : $value,
            $settings,
        );

        $request->merge(['settings' => $settings === [] ? null : $settings]);

        // Unchecked checkbox groups submit nothing; an empty group means none.
        $request->mergeIfMissing(['tools' => [], 'sub_agents' => [], 'concrete_sub_agents' => []]);

        return $request->all();
    }
}
