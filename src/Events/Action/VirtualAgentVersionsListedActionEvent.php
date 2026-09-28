<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;

/**
 * A page of a virtual agent's prompt versions was listed.
 */
final class VirtualAgentVersionsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  LengthAwarePaginator<int, VirtualAgentVersion>  $versions
     */
    public function __construct(
        public VirtualAgent $agent,
        public LengthAwarePaginator $versions,
    ) {}
}
