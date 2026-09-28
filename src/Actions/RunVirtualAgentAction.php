<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use JayI\Cortex\Events\Action\VirtualAgentRanActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentRunningActionEvent;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Runtime\AgentFactory;
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

    public function execute(VirtualAgent $agent, string $input): AgentResponse
    {
        VirtualAgentRunningActionEvent::dispatch($agent, $input);

        $result = $this->perform($agent, $input);

        VirtualAgentRanActionEvent::dispatch($agent, $input, $result);

        return $result;
    }

    private function perform(VirtualAgent $agent, string $input): AgentResponse
    {
        return $this->factory->make($agent)->prompt($input);
    }
}
