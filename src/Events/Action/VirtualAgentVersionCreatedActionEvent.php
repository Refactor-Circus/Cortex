<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;

/**
 * A new version of a virtual agent's prompt was created, and published when asked.
 */
final class VirtualAgentVersionCreatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public VirtualAgent $agent,
        public VirtualAgentVersion $version,
    ) {}
}
