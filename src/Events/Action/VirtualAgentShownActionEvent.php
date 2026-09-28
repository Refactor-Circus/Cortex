<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\VirtualAgent;

/**
 * A virtual agent was shown, with its prompt and sub-agents loaded.
 */
final class VirtualAgentShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgent $agent,
    ) {}
}
