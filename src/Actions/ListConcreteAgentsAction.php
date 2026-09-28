<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Events\Action\ConcreteAgentsListedActionEvent;
use JayI\Cortex\Events\Action\ConcreteAgentsListingActionEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;
use Laravel\Ai\Contracts\Agent;

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
     * @return list<array{name: string, class: class-string<Agent>, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_tools: list<string>, override: ConcreteAgentOverride|null}>
     */
    public function execute(): array
    {
        ConcreteAgentsListingActionEvent::dispatch();

        $result = $this->perform();

        ConcreteAgentsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return list<array{name: string, class: class-string<Agent>, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_tools: list<string>, override: ConcreteAgentOverride|null}>
     */
    private function perform(): array
    {
        $overrides = ConcreteAgentOverride::query()
            ->with('publishedVersion')
            ->get()
            ->keyBy('agent');

        return array_map(
            fn (array $agent): array => [...$agent, 'override' => $overrides->get($agent['name'])],
            $this->registry->all(),
        );
    }
}
