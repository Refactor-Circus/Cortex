<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Foundation\Contracts\ActionFinishedEvent;
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
