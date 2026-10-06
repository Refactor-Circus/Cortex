<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Foundation\Contracts\ActionFinishedEvent;

/**
 * A page of a virtual agent's prompt versions was listed.
 */
final class VirtualAgentVersionsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  LengthAwarePaginator<int, VirtualAgentVersionModel>  $versions
     */
    public function __construct(
        public VirtualAgentModel $agent,
        public LengthAwarePaginator $versions,
    ) {}
}
