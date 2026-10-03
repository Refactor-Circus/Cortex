<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Actions;

use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentRanActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentRunningActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Services\AgentFactory;
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
