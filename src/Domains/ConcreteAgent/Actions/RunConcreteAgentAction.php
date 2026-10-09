<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Laravel\Ai\Responses\AgentResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentRanActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentRunningActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;

final class RunConcreteAgentAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'input' => ['required', 'string'],
        ];
    }

    public function __construct(private readonly AgentRegistry $registry) {}

    public function execute(string $agent, string $input): AgentResponse
    {
        ConcreteAgentRunningActionEvent::dispatch($agent, $input);

        $result = $this->perform($agent, $input);

        ConcreteAgentRanActionEvent::dispatch($agent, $input, $result);

        return $result;
    }

    private function perform(string $agent, string $input): AgentResponse
    {
        return $this->registry->make($agent)->prompt($input);
    }
}
