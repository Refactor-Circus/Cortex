<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RefactorCircus\Atrium\Domains\Screens\Data\ResolvedScreen;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Cortex\Atrium\CortexPlugin;
use RefactorCircus\Cortex\Atrium\RunnableAgents;
use RefactorCircus\Cortex\Atrium\Screens;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\RunConcreteAgentData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\RunVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\RunVirtualAgentData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

/**
 * Runs either kind of agent. The select submits `virtual:{slug}` or
 * `concrete:{name}` so one field covers both, and offers only the agents the
 * user may run.
 */
final class RunAgentUiController
{
    public function create(Request $request, ScreenExtensions $extensions): View
    {
        abort_unless(CortexPlugin::mayViewAgents($request->user()), 403);

        $options = RunnableAgents::for($request->user());

        /** @var view-string $view */
        $view = 'cortex::ui.run';

        return view($view, [
            'agents' => $options,
            'selected' => $request->string('agent')->toString(),
            'result' => null,
            'resolved' => $this->screen($extensions, $request),
        ]);
    }

    public function store(Request $request, ScreenExtensions $extensions): View
    {
        $data = $request->validate([
            'agent' => ['required', 'string', Rule::in(array_keys(RunnableAgents::all()))],
        ] + RunVirtualAgentData::rules() + RunConcreteAgentData::rules());

        abort_unless(RunnableAgents::allows($request->user(), (string) $data['agent']), 403);

        // Before the run, so a `?tab=` the viewer may not open refuses the
        // request without calling the agent.
        $resolved = $this->screen($extensions, $request);

        [$kind, $key] = explode(':', (string) $data['agent'], 2);

        $response = $kind === 'virtual'
            ? app(RunVirtualAgentAction::class)->execute(
                VirtualAgentModel::query()->where('slug', $key)->firstOrFail(),
                RunVirtualAgentData::fromValidated($data),
            )
            : app(RunConcreteAgentAction::class)->execute($key, RunConcreteAgentData::fromValidated($data));

        /** @var view-string $view */
        $view = 'cortex::ui.run';

        return view($view, [
            'agents' => RunnableAgents::for($request->user()),
            'selected' => $data['agent'],
            'input' => $data['input'],
            'result' => $response,
            'resolved' => $resolved,
        ]);
    }

    /**
     * The run page, with no record and no subject: an extension's
     * `permission()` is never granted here.
     */
    private function screen(ScreenExtensions $extensions, Request $request): ResolvedScreen
    {
        return $extensions->resolve(Screens::declare('cortex.run', Screen::DETAIL, null, null), $request);
    }
}
