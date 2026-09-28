<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\VirtualAgent;

/**
 * A page of virtual agents was listed.
 */
final class VirtualAgentsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  LengthAwarePaginator<int, VirtualAgent>  $agents
     */
    public function __construct(
        public LengthAwarePaginator $agents,
    ) {}
}
