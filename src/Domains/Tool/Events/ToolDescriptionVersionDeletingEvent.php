<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The ToolDescriptionVersionModel `deleting` Eloquent event.
 */
final class ToolDescriptionVersionDeletingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ToolDescriptionVersionModel $version) {}

    public function model(): Model
    {
        return $this->version;
    }

    public function hook(): string
    {
        return 'deleting';
    }
}
