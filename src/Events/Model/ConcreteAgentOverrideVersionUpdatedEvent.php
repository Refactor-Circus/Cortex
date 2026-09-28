<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ModelLifecycleEvent;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

/**
 * The ConcreteAgentOverrideVersion `updated` Eloquent event.
 */
final class ConcreteAgentOverrideVersionUpdatedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ConcreteAgentOverrideVersion $version) {}

    public function model(): Model
    {
        return $this->version;
    }

    public function hook(): string
    {
        return 'updated';
    }
}
