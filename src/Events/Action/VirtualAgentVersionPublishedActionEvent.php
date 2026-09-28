<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\VirtualAgent;

/**
 * A version of a virtual agent's prompt was published; `$agent->publishedVersion` is the new one.
 */
final class VirtualAgentVersionPublishedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgent $agent,
    ) {}
}
