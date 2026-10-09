<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The ConcreteAgentOverrideVersionModel `retrieved` Eloquent event.
 */
final class ConcreteAgentOverrideVersionRetrievedEvent implements ModelLifecycleEvent
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
        return 'retrieved';
    }
}
