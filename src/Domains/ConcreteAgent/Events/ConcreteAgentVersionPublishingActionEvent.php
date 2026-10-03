<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionStartingEvent;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;

/**
 * A version of a concrete agent's prompt override is about to be published.
 */
final class ConcreteAgentVersionPublishingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ConcreteAgentOverrideModel $override,
        public int $version,
    ) {}
}
