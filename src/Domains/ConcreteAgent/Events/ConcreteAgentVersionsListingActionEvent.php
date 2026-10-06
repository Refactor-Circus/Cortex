<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Foundation\Contracts\ActionStartingEvent;

/**
 * A concrete agent's prompt override versions are about to be listed.
 */
final class ConcreteAgentVersionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ConcreteAgentOverrideModel $override,
    ) {}
}
