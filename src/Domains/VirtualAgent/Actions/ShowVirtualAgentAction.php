<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentShowingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentShownActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class ShowVirtualAgentAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(VirtualAgentModel $agent): VirtualAgentModel
    {
        VirtualAgentShowingActionEvent::dispatch($agent);

        $result = $this->perform($agent);

        VirtualAgentShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(VirtualAgentModel $agent): VirtualAgentModel
    {
        return $agent->load(['publishedVersion', 'subAgents']);
    }
}
