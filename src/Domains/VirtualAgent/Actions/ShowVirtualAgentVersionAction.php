<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Actions;

use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionShowingActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionShownActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;

final class ShowVirtualAgentVersionAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(VirtualAgentModel $agent, int $version): VirtualAgentVersionModel
    {
        VirtualAgentVersionShowingActionEvent::dispatch($agent, $version);

        $result = $this->perform($agent, $version);

        VirtualAgentVersionShownActionEvent::dispatch($agent, $result);

        return $result;
    }

    private function perform(VirtualAgentModel $agent, int $version): VirtualAgentVersionModel
    {
        /** @var VirtualAgentVersionModel */
        return $agent->versions()->where('version', $version)->firstOrFail();
    }
}
