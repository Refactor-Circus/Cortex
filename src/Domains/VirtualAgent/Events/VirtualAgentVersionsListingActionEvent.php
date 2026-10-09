<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A virtual agent's prompt versions are about to be listed.
 */
final class VirtualAgentVersionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgentModel $agent,
        public ?int $page,
    ) {}
}
