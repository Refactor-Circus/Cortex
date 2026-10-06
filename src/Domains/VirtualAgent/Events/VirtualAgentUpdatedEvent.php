<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Foundation\Contracts\ModelLifecycleEvent;

/**
 * The VirtualAgentModel `updated` Eloquent event.
 */
final class VirtualAgentUpdatedEvent implements ModelLifecycleEvent
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
        return 'updated';
    }
}
