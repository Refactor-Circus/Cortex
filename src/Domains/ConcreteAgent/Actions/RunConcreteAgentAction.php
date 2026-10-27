<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Laravel\Ai\Responses\AgentResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\RunConcreteAgentData;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentRanActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentRunningActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Keystone\Actions\Action;

final class RunConcreteAgentAction extends Action
{
    public function __construct(private readonly AgentRegistry $registry) {}

    protected function handle(string $agent, RunConcreteAgentData $data): AgentResponse
    {
        ConcreteAgentRunningActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        ConcreteAgentRanActionEvent::dispatch($agent, $data, $result);

        return $result;
    }

    private function perform(string $agent, RunConcreteAgentData $data): AgentResponse
    {
        return $this->registry->make($agent)->prompt($data->input);
    }
}
