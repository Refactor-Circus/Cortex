<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The VirtualAgentModel `replicating` Eloquent event.
 */
final class VirtualAgentReplicatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public VirtualAgentModel $agent) {}

    public function model(): Model
    {
        return $this->agent;
    }

    public function hook(): string
    {
        return 'replicating';
    }
}
