<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionStartingEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;

/**
 * A version of a concrete agent's prompt override is about to be published.
 */
final class ConcreteAgentVersionPublishingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ConcreteAgentOverride $override,
        public int $version,
    ) {}
}
