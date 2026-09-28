<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionStartingEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;

/**
 * A concrete agent's overrides are about to be deleted.
 */
final class ConcreteAgentOverrideDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ConcreteAgentOverride $override,
    ) {}
}
