<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Actions;

use JayI\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentRanActionEvent;
use JayI\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentRunningActionEvent;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use Laravel\Ai\Responses\AgentResponse;

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
