<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Foundation\Contracts\ModelLifecycleEvent;

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
