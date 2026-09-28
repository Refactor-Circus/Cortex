<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use JayI\Cortex\Actions\RunConcreteAgentAction;
use JayI\Cortex\Actions\RunVirtualAgentAction;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Models\VirtualAgent;

/**
 * Runs either kind of agent. The select submits `virtual:{slug}` or
 * `concrete:{name}` so one field covers both.
 */
final class RunAgentUiController
{
    public function create(Request $request): View
    {
        /** @var view-string $view */
        $view = 'cortex::ui.run';

        return view($view, [
            'agents' => $this->options(),
            'selected' => $request->string('agent')->toString(),
            'result' => null,
        ]);
    }

    public function store(Request $request): View
    {
        $options = $this->options();

        $data = $request->validate([
            'agent' => ['required', 'string', Rule::in(array_keys($options))],
            'input' => ['required', 'string'],
        ]);

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
            'agents' => $options,
            'selected' => $data['agent'],
            'input' => $data['input'],
            'result' => $response,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function options(): array
    {
        $virtual = VirtualAgent::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (VirtualAgent $agent): array => [
                'virtual:'.$agent->slug => $agent->name.' ('.__('cortex::cortex.virtual').')',
            ])
            ->all();

        $concrete = collect(app(AgentRegistry::class)->names())
            ->sort()
            ->mapWithKeys(fn (string $name): array => [
                'concrete:'.$name => $name.' ('.__('cortex::cortex.concrete').')',
            ])
            ->all();

        return [...$virtual, ...$concrete];
    }
}
