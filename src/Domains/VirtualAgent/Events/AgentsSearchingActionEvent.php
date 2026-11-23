<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\SearchAgentsData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * Virtual and concrete agents are about to be searched for a caller.
 */
final class AgentsSearchingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SearchAgentsData $data,
        public ?Authenticatable $user = null,
    ) {}
}
