<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use JayI\Cortex\Events\Action\VirtualAgentVersionShowingActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentVersionShownActionEvent;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;

final class ShowVirtualAgentVersionAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(VirtualAgent $agent, int $version): VirtualAgentVersion
    {
        VirtualAgentVersionShowingActionEvent::dispatch($agent, $version);

        $result = $this->perform($agent, $version);

        VirtualAgentVersionShownActionEvent::dispatch($agent, $result);

        return $result;
    }

    private function perform(VirtualAgent $agent, int $version): VirtualAgentVersion
    {
        /** @var VirtualAgentVersion */
        return $agent->versions()->where('version', $version)->firstOrFail();
    }
}
