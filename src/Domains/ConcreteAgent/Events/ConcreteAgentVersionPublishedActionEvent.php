<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Foundation\Contracts\ActionFinishedEvent;

/**
 * A version of a concrete agent's prompt override was published; `$override->publishedVersion` is the new one.
 */
final class ConcreteAgentVersionPublishedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ConcreteAgentOverrideModel $override,
    ) {}
}
