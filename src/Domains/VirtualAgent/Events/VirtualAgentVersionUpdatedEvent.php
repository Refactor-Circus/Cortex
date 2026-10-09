<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The VirtualAgentVersionModel `updated` Eloquent event.
 */
final class VirtualAgentVersionUpdatedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public VirtualAgentVersionModel $version) {}

    public function model(): Model
    {
        return $this->version;
    }

    public function hook(): string
    {
        return 'updated';
    }
}
