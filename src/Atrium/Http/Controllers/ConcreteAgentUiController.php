<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Cortex\Atrium\ScreenAccess;
use RefactorCircus\Cortex\Atrium\Screens;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\DeleteConcreteAgentOverrideAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\PublishConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ShowConcreteAgentAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\CreateConcreteAgentVersionData;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\UpdateConcreteAgentToolsData;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;

final class ConcreteAgentUiController
{
    use AuthorizesScreens;

    public function index(Request $request, ScreenExtensions $extensions): View
    {
        $this->authorizeScreen('viewAny', ConcreteAgentOverrideModel::class);

        /** @var view-string $view */
        $view = 'cortex::ui.concrete-agents.index';

        return view($view, [
            'agents' => app(ListConcreteAgentsAction::class)->execute(),
            'resolved' => $extensions->resolve(Screens::declare('cortex.concrete-agents', Screen::LIST, null, ConcreteAgentOverrideModel::class), $request),
        ]);
    }

    public function show(Request $request, ScreenExtensions $extensions, string $agent): View
    {
        $this->assertRegistered($agent);

        $details = app(ShowConcreteAgentAction::class)->execute($agent);
        $subject = ScreenAccess::concreteAgent($agent, $details['override']);

        $this->authorizeScreen('view', $subject);

        /** @var view-string $view */
        $view = 'cortex::ui.concrete-agents.show';

        return view($view, [
            'agent' => $details,
            'override' => $details['override'],
            'subject' => $subject,
            'versions' => $details['override']?->versions()->chaperone('concreteAgentOverride')->orderByDesc('version')->get() ?? collect(),
            'availableTools' => $this->availableTools($details['default_tools']),
            'resolved' => $extensions->resolve(Screens::declare('cortex.concrete-agent', Screen::DETAIL, $subject, null), $request),
        ]);
    }

    public function store(Request $request, string $agent): RedirectResponse
    {
        $this->assertRegistered($agent);

        $this->authorizeScreen('create', ConcreteAgentOverrideVersionModel::class, [ScreenAccess::concreteAgent($agent, $this->override($agent))]);

        app(CreateConcreteAgentVersionAction::class)->execute($agent, CreateConcreteAgentVersionData::from($request->all()));

        return redirect()
            ->route('atrium.cortex.concrete-agents.show', $agent)
            ->with('status', __('cortex::cortex.version_created'));
    }

    public function publish(string $agent, int $version): RedirectResponse
    {
        $this->assertRegistered($agent);

        $override = $this->override($agent);

        abort_if($override === null, 404);

        $this->authorizeScreen('publish', $override->versions()->where('version', $version)->firstOrFail());

        app(PublishConcreteAgentVersionAction::class)->execute($override, $version);

        return redirect()
            ->route('atrium.cortex.concrete-agents.show', $agent)
            ->with('status', __('cortex::cortex.version_published'));
    }

    /**
     * Save the checked tools as the override, or clear it when the form asks
     * to go back to the toolset declared in code.
     */
    public function tools(Request $request, string $agent): RedirectResponse
    {
        $this->assertRegistered($agent);

        $this->authorizeScreen('update', ScreenAccess::concreteAgent($agent, $this->override($agent)));

        $data = UpdateConcreteAgentToolsData::from([
            'tools' => $request->boolean('use_code_tools') ? null : array_values((array) $request->input('tools', [])),
        ], $agent);

        app(UpdateConcreteAgentToolsAction::class)->execute($agent, $data);

        return redirect()
            ->route('atrium.cortex.concrete-agents.show', $agent)
            ->with('status', __('cortex::cortex.tools_updated'));
    }

    public function destroy(string $agent): RedirectResponse
    {
        $this->assertRegistered($agent);

        $override = $this->override($agent);

        abort_if($override === null, 404);

        $this->authorizeScreen('delete', $override);

        app(DeleteConcreteAgentOverrideAction::class)->execute($override);

        return redirect()
            ->route('atrium.cortex.concrete-agents.show', $agent)
            ->with('status', __('cortex::cortex.override_removed'));
    }

    /**
     * The agent's override row, or null when it has none.
     */
    private function override(string $agent): ?ConcreteAgentOverrideModel
    {
        return ConcreteAgentOverrideModel::query()->where('agent', $agent)->first();
    }

    private function assertRegistered(string $agent): void
    {
        abort_unless(app(AgentRegistry::class)->has($agent), 404);
    }

    /**
     * The tools the agent can be given, with their tags: its code-declared
     * tools first, then every registered one. A code-declared tool Cortex
     * does not know has no tags.
     *
     * @param  list<string>  $defaults
     * @return list<array{name: string, tags: list<string>}>
     */
    private function availableTools(array $defaults): array
    {
        $registry = app(ToolRegistry::class);

        return array_map(fn (string $name): array => [
            'name' => $name,
            'tags' => $registry->has($name) ? $registry->tagsFor($name) : [],
        ], array_values(array_unique([...$defaults, ...$registry->names()])));
    }
}
