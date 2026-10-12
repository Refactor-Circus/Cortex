<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\ListVirtualAgentsData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * Virtual agents are about to be listed.
 */
final class VirtualAgentsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListVirtualAgentsData $filters,
    ) {}
}
