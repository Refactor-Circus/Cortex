<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionStartingEvent;
use JayI\Cortex\Models\VirtualAgent;

/**
 * A version of a virtual agent's prompt is about to be published.
 */
final class VirtualAgentVersionPublishingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgent $agent,
        public int $version,
    ) {}
}
