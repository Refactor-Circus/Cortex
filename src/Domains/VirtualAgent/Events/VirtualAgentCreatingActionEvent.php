<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\CreateVirtualAgentData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A virtual agent is about to be created.
 */
final class VirtualAgentCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CreateVirtualAgentData $data,
    ) {}
}
