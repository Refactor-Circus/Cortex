<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\RunnableAgentsListedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\RunnableAgentsListingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListRunnableAgentsAction extends Action
{
    public function __construct(private readonly AgentRegistry $agents) {}

    /**
     * List every agent the caller may run: virtual agents ordered by name,
     * then registered concrete agents ordered by name, each kept only when
     * the caller passes `run` on it.
     *
     * @return list<array{type: string, key: string, name: string}>
     */
    protected function handle(?Authenticatable $user = null): array
    {
        RunnableAgentsListingActionEvent::dispatch($user);

        $result = $this->perform(Gate::forUser($user));

        RunnableAgentsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return list<array{type: string, key: string, name: string}>
     */
    private function perform(GateContract $gate): array
    {
        return [...$this->virtualAgents($gate), ...$this->concreteAgents($gate)];
    }

    /**
     * @return list<array{type: string, key: string, name: string}>
     */
    private function virtualAgents(GateContract $gate): array
    {
        return array_values(VirtualAgentModel::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (VirtualAgentModel $agent): bool => $gate->allows('run', $agent))
            ->map(fn (VirtualAgentModel $agent): array => [
                'type' => 'virtual_agent',
                'key' => (string) $agent->slug,
                'name' => (string) $agent->name,
            ])
            ->all());
    }

    /**
     * Each registered concrete agent is checked against its saved override,
     * all fetched in one query, or an unsaved one when it has none.
     *
     * @return list<array{type: string, key: string, name: string}>
     */
    private function concreteAgents(GateContract $gate): array
    {
        $names = $this->agents->names();
        sort($names, SORT_STRING);

        if ($names === []) {
            return [];
        }

        $overrides = ConcreteAgentOverrideModel::query()->whereIn('agent', $names)->get()->keyBy('agent');

        return array_values(array_map(
            fn (string $name): array => ['type' => 'concrete_agent', 'key' => $name, 'name' => $name],
            array_filter(
                $names,
                fn (string $name): bool => $gate->allows('run', $overrides->get($name) ?? new ConcreteAgentOverrideModel(['agent' => $name])),
            ),
        ));
    }
}
