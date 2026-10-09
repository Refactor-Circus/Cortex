<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Laravel\Ai\Contracts\Agent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentShowingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentShownActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;

final class ShowConcreteAgentAction
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
     * @return array{name: string, class: class-string<Agent>, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_instructions: string, default_tools: list<string>, override: ConcreteAgentOverrideModel|null}
     */
    public function execute(string $agent): array
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
        $agent = $this->registry->make($name);

        return [
            'name' => $name,
            'class' => $this->registry->get($name),
            'overridable' => $this->registry->supportsOverrides($name),
            'tools_overridable' => $this->registry->supportsToolOverrides($name),
            'instructions' => (string) $agent->instructions(),
            'tools' => $this->registry->effectiveTools($name),
            'default_instructions' => $this->registry->defaultInstructions($name),
            'default_tools' => $this->registry->defaultTools($name),
            'override' => ConcreteAgentOverrideModel::query()->where('agent', $name)->with('publishedVersion')->first(),
        ];
    }
}
