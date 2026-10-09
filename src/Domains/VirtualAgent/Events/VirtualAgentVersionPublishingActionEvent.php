<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A version of a virtual agent's prompt is about to be published.
 */
final class VirtualAgentVersionPublishingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgentModel $agent,
        public int $version,
    ) {}
}
