<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Foundation\Contracts\ModelLifecycleEvent;

/**
 * The ConcreteAgentOverrideModel `updating` Eloquent event.
 */
final class ConcreteAgentOverrideUpdatingEvent implements ModelLifecycleEvent
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
        return 'updating';
    }
}
