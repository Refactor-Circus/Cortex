<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Foundation\Contracts\ActionFinishedEvent;

/**
 * A virtual agent was updated.
 */
final class VirtualAgentUpdatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgentModel $agent,
    ) {}
}
