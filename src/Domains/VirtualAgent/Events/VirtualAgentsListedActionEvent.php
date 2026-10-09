<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A page of virtual agents was listed.
 */
final class VirtualAgentsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  LengthAwarePaginator<int, VirtualAgentModel>  $agents
     */
    public function __construct(
        public LengthAwarePaginator $agents,
    ) {}
}
