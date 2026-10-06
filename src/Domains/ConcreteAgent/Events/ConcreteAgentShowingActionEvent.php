<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionStartingEvent;

/**
 * A concrete agent is about to be shown.
 */
final class ConcreteAgentShowingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $agent,
    ) {}
}
