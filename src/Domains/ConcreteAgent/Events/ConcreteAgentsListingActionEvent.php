<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * Concrete agents are about to be listed.
 */
final class ConcreteAgentsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;
}
