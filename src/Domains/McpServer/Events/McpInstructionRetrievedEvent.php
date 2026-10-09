<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The McpInstructionModel `retrieved` Eloquent event.
 */
final class McpInstructionRetrievedEvent implements ModelLifecycleEvent
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
        return 'retrieved';
    }
}
