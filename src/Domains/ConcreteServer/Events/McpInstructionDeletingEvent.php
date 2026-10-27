<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The McpInstructionModel `deleting` Eloquent event.
 */
final class McpInstructionDeletingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public McpInstructionModel $instruction) {}

    public function model(): Model
    {
        return $this->instruction;
    }

    public function hook(): string
    {
        return 'deleting';
    }
}
