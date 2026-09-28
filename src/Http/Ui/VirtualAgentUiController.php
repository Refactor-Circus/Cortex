<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Cortex\Actions\CreateVirtualAgentAction;
use JayI\Cortex\Actions\DeleteVirtualAgentAction;
use JayI\Cortex\Actions\ListProvidersAction;
use JayI\Cortex\Actions\ListToolsAction;
use JayI\Cortex\Actions\ListVirtualAgentsAction;
use JayI\Cortex\Actions\UpdateVirtualAgentAction;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Models\VirtualAgent;

final class VirtualAgentUiController
{
    public function index(Request $request): View
    {
        /** @var view-string $view */
        $view = 'cortex::ui.virtual-agents.index';

        return view($view, [
            'agents' => app(ListVirtualAgentsAction::class)->execute($request->integer('page') ?: null),
        ]);
    }

    public function create(): View
    {
        /** @var view-string $view */
        $view = 'cortex::ui.virtual-agents.form';

        return view($view, [...$this->formData(), 'agent' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, CreateVirtualAgentAction::rules());

        app(CreateVirtualAgentAction::class)->execute($data);

        return redirect()
            ->route('atrium.cortex.virtual-agents.index')
            ->with('status', __('cortex::cortex.agent_created'));
    }

    public function edit(VirtualAgent $agent): View
    {
        /** @var view-string $view */
        $view = 'cortex::ui.virtual-agents.form';

        return view($view, [
            ...$this->formData($agent),
            'agent' => $agent->load(['publishedVersion', 'subAgents']),
            'versions' => $agent->versions()->orderByDesc('version')->get(),
        ]);
    }

    public function update(Request $request, VirtualAgent $agent): RedirectResponse
    {
        $data = $this->validated($request, UpdateVirtualAgentAction::rules());

        app(UpdateVirtualAgentAction::class)->execute($agent, $data);

        return redirect()
            ->route('atrium.cortex.virtual-agents.edit', $agent->slug)
            ->with('status', __('cortex::cortex.agent_updated'));
    }

    public function destroy(VirtualAgent $agent): RedirectResponse
    {
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
    private function formData(?VirtualAgent $agent = null): array
    {
        $providers = app(ListProvidersAction::class)->execute();

        $names = array_map(fn (array $provider): string => (string) $provider['name'], $providers);

        if ($agent?->provider !== null && ! in_array($agent->provider, $names, true)) {
            array_unshift($names, $agent->provider);
        }

        return [
            'providers' => $providers,
            'providerNames' => $names,
            'tools' => app(ListToolsAction::class)->execute(),
            'concreteAgents' => app(AgentRegistry::class)->names(),
            'agents' => VirtualAgent::query()
                ->when($agent !== null, fn (Builder $query): Builder => $query->whereKeyNot($agent?->getKey()))
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Validate after dropping blank settings.
     *
     * An HTML form submits every number input, so an untouched field arrives
     * as an empty string. Those are absent values, not invalid ones, and must
     * be removed before the rules run rather than after.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function validated(Request $request, array $rules): array
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

        return $request->validate($rules);
    }
}
