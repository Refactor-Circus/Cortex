<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Foundation\Contracts\ModelLifecycleEvent;

/**
 * The VirtualAgentVersionModel `saving` Eloquent event.
 */
final class VirtualAgentVersionSavingEvent implements ModelLifecycleEvent
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
        return 'saving';
    }
}
