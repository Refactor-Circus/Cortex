<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use Laravel\Ai\Responses\AgentResponse;

/**
 * A virtual agent ran and responded.
 */
final class VirtualAgentRanActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgentModel $agent,
        public string $input,
        public AgentResponse $response,
    ) {}
}
