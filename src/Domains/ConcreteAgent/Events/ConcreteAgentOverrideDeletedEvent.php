<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ModelLifecycleEvent;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;

/**
 * The ConcreteAgentOverrideModel `deleted` Eloquent event.
 */
final class ConcreteAgentOverrideDeletedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ConcreteAgentOverrideModel $override) {}

    public function model(): Model
    {
        return $this->override;
    }

    public function hook(): string
    {
        return 'deleted';
    }
}
