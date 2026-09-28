<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;

/**
 * A concrete agent's overrides were deleted; the agent falls back to its code-declared prompt and toolset.
 */
final class ConcreteAgentOverrideDeletedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ConcreteAgentOverride $override,
    ) {}
}
