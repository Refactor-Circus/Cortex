<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionStartingEvent;
use JayI\Cortex\Models\VirtualAgent;

/**
 * A virtual agent's prompt versions are about to be listed.
 */
final class VirtualAgentVersionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgent $agent,
        public ?int $page,
    ) {}
}
