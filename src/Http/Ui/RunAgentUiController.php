<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use JayI\Cortex\Actions\RunConcreteAgentAction;
use JayI\Cortex\Actions\RunVirtualAgentAction;
use JayI\Cortex\Atrium\CortexPlugin;
use JayI\Cortex\Models\VirtualAgent;

/**
 * Runs either kind of agent. The select submits `virtual:{slug}` or
 * `concrete:{name}` so one field covers both, and offers only the agents the
 * user may run.
 */
final class RunAgentUiController
{
    public function create(Request $request): View
    {
        abort_unless(CortexPlugin::mayViewAgents($request->user()), 403);

        $options = RunnableAgents::for($request->user());

        /** @var view-string $view */
        $view = 'cortex::ui.run';

        return view($view, [
            'agents' => $options,
            'selected' => $request->string('agent')->toString(),
            'result' => null,
        ]);
    }

    public function store(Request $request): View
    {
        $data = $request->validate([
            'agent' => ['required', 'string', Rule::in(array_keys(RunnableAgents::all()))],
            'input' => ['required', 'string'],
        ]);

        abort_unless(RunnableAgents::allows($request->user(), (string) $data['agent']), 403);

        [$kind, $key] = explode(':', (string) $data['agent'], 2);

        $response = $kind === 'virtual'
            ? app(RunVirtualAgentAction::class)->execute(
                VirtualAgent::query()->where('slug', $key)->firstOrFail(),
                $data['input'],
            )
            : app(RunConcreteAgentAction::class)->execute($key, $data['input']);

        /** @var view-string $view */
        $view = 'cortex::ui.run';

        return view($view, [
            'agents' => RunnableAgents::for($request->user()),
            'selected' => $data['agent'],
            'input' => $data['input'],
            'result' => $response,
        ]);
    }
}
