<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentRanActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentRunningActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Services\AgentFactory;
use Laravel\Ai\Responses\AgentResponse;

final class RunVirtualAgentAction
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

    public function __construct(private readonly AgentFactory $factory) {}

    public function execute(VirtualAgentModel $agent, string $input): AgentResponse
    {
        VirtualAgentRunningActionEvent::dispatch($agent, $input);

        $result = $this->perform($agent, $input);

        VirtualAgentRanActionEvent::dispatch($agent, $input, $result);

        return $result;
    }

    private function perform(VirtualAgentModel $agent, string $input): AgentResponse
    {
        return $this->factory->make($agent)->prompt($input);
    }
}
