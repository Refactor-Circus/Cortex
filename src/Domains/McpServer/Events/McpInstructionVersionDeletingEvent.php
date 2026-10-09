<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The McpInstructionVersionModel `deleting` Eloquent event.
 */
final class McpInstructionVersionDeletingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public McpInstructionVersionModel $version) {}

    public function model(): Model
    {
        return $this->version;
    }

    public function hook(): string
    {
        return 'deleting';
    }
}
