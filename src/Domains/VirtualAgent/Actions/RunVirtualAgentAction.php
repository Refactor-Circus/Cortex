<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Laravel\Ai\Responses\AgentResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\RunVirtualAgentData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentRanActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentRunningActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Services\AgentFactory;

final class RunVirtualAgentAction
{
    public function __construct(private readonly AgentFactory $factory) {}

    public function execute(VirtualAgentModel $agent, RunVirtualAgentData $data): AgentResponse
    {
        VirtualAgentRunningActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        VirtualAgentRanActionEvent::dispatch($agent, $data, $result);

        return $result;
    }

    private function perform(VirtualAgentModel $agent, RunVirtualAgentData $data): AgentResponse
    {
        return $this->factory->make($agent)->prompt($data->input);
    }
}
