<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Laravel\Ai\Contracts\Agent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentShowingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentShownActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Keystone\Actions\Action;

final class ShowConcreteAgentAction extends Action
{
    public function __construct(private readonly AgentRegistry $registry) {}

    /**
     * @return array{name: string, class: class-string<Agent>, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_instructions: string, default_tools: list<string>, override: ConcreteAgentOverrideModel|null}
     */
    protected function handle(string $agent): array
    {
        ConcreteAgentShowingActionEvent::dispatch($agent);

        $result = $this->perform($agent);

        ConcreteAgentShownActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return array{name: string, class: class-string<Agent>, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_instructions: string, default_tools: list<string>, override: ConcreteAgentOverrideModel|null}
     */
    private function perform(string $name): array
    {
        return [
            ...$this->registry->describe($name),
            'override' => ConcreteAgentOverrideModel::query()->where('agent', $name)->with('publishedVersion')->first(),
        ];
    }
}
