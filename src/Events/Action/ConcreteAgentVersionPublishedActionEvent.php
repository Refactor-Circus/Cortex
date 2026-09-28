<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;

/**
 * A version of a concrete agent's prompt override was published; `$override->publishedVersion` is the new one.
 */
final class ConcreteAgentVersionPublishedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ConcreteAgentOverride $override,
    ) {}
}
