<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ModelLifecycleEvent;
use JayI\Cortex\Models\VirtualAgent;

/**
 * The VirtualAgent `created` Eloquent event.
 */
final class VirtualAgentCreatedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public VirtualAgent $agent) {}

    public function model(): Model
    {
        return $this->agent;
    }

    public function hook(): string
    {
        return 'created';
    }
}
