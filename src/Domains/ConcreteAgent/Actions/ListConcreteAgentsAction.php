<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Laravel\Ai\Contracts\Agent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentsListedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentsListingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;

final class ListConcreteAgentsAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function __construct(private readonly AgentRegistry $registry) {}

    /**
     * @return list<array{name: string, class: class-string<Agent>, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_tools: list<string>, override: ConcreteAgentOverrideModel|null}>
     */
    public function execute(): array
    {
        ConcreteAgentsListingActionEvent::dispatch();

        $result = $this->perform();

        ConcreteAgentsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return list<array{name: string, class: class-string<Agent>, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_tools: list<string>, override: ConcreteAgentOverrideModel|null}>
     */
    private function perform(): array
    {
        $overrides = ConcreteAgentOverrideModel::query()
            ->with('publishedVersion')
            ->get()
            ->keyBy('agent');

        return array_map(
            fn (array $agent): array => [...$agent, 'override' => $overrides->get($agent['name'])],
            $this->registry->all(),
        );
    }
}
