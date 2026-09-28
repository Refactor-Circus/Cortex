<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Cortex\Actions\CreateConcreteAgentVersionAction;
use JayI\Cortex\Actions\DeleteConcreteAgentOverrideAction;
use JayI\Cortex\Actions\ListConcreteAgentsAction;
use JayI\Cortex\Actions\PublishConcreteAgentVersionAction;
use JayI\Cortex\Actions\ShowConcreteAgentAction;
use JayI\Cortex\Actions\UpdateConcreteAgentToolsAction;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Tools\ToolRegistry;

final class ConcreteAgentUiController
{
    public function index(): View
    {
        /** @var view-string $view */
        $view = 'cortex::ui.concrete-agents.index';

        return view($view, ['agents' => app(ListConcreteAgentsAction::class)->execute()]);
    }

    public function show(string $agent): View
    {
        $this->assertRegistered($agent);

        $details = app(ShowConcreteAgentAction::class)->execute($agent);

        /** @var view-string $view */
        $view = 'cortex::ui.concrete-agents.show';

        return view($view, [
            'agent' => $details,
            'override' => $details['override'],
            'versions' => $details['override']?->versions()->orderByDesc('version')->get() ?? collect(),
            'availableTools' => array_values(array_unique([
                ...$details['default_tools'],
                ...app(ToolRegistry::class)->names(),
            ])),
        ]);
    }

    public function store(Request $request, string $agent): RedirectResponse
    {
        $this->assertRegistered($agent);

        $data = $request->validate(CreateConcreteAgentVersionAction::rules());

        app(CreateConcreteAgentVersionAction::class)->execute($agent, $data);

        return redirect()
            ->route('atrium.cortex.concrete-agents.show', $agent)
            ->with('status', __('cortex::cortex.version_created'));
    }

    public function publish(string $agent, int $version): RedirectResponse
    {
        $this->assertRegistered($agent);

        $override = app(ShowConcreteAgentAction::class)->execute($agent)['override'];

        abort_if($override === null, 404);

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

        $request->merge([
            'tools' => $request->boolean('use_code_tools') ? null : array_values((array) $request->input('tools', [])),
        ]);

        /** @var array{tools: list<string>|null} $data */
        $data = $request->validate(UpdateConcreteAgentToolsAction::rules($agent));

        app(UpdateConcreteAgentToolsAction::class)->execute($agent, $data['tools']);

        return redirect()
            ->route('atrium.cortex.concrete-agents.show', $agent)
            ->with('status', __('cortex::cortex.tools_updated'));
    }

    public function destroy(string $agent): RedirectResponse
    {
        $this->assertRegistered($agent);

        $override = app(ShowConcreteAgentAction::class)->execute($agent)['override'];

        abort_if($override === null, 404);

        app(DeleteConcreteAgentOverrideAction::class)->execute($override);

        return redirect()
            ->route('atrium.cortex.concrete-agents.show', $agent)
            ->with('status', __('cortex::cortex.override_removed'));
    }

    private function assertRegistered(string $agent): void
    {
        abort_unless(app(AgentRegistry::class)->has($agent), 404);
    }
}
