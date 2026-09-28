<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\VirtualAgent;
use Laravel\Ai\Responses\AgentResponse;

/**
 * A virtual agent ran and responded.
 */
final class VirtualAgentRanActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgent $agent,
        public string $input,
        public AgentResponse $response,
    ) {}
}
