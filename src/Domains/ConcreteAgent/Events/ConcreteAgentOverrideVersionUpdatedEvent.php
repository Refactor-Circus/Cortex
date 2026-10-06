<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use JayI\Foundation\Contracts\ModelLifecycleEvent;

/**
 * The ConcreteAgentOverrideVersionModel `updated` Eloquent event.
 */
final class ConcreteAgentOverrideVersionUpdatedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ConcreteAgentOverrideVersionModel $version) {}

    public function model(): Model
    {
        return $this->version;
    }

    public function hook(): string
    {
        return 'updated';
    }
}
