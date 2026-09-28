<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use JayI\Cortex\Events\Action\VirtualAgentShowingActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentShownActionEvent;
use JayI\Cortex\Models\VirtualAgent;

final class ShowVirtualAgentAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(VirtualAgent $agent): VirtualAgent
    {
        VirtualAgentShowingActionEvent::dispatch($agent);

        $result = $this->perform($agent);

        VirtualAgentShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(VirtualAgent $agent): VirtualAgent
    {
        return $agent->load(['publishedVersion', 'subAgents']);
    }
}
