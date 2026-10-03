<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;

/**
 * A concrete agent's overrides were deleted; the agent falls back to its code-declared prompt and toolset.
 */
final class ConcreteAgentOverrideDeletedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ConcreteAgentOverrideModel $override,
    ) {}
}
