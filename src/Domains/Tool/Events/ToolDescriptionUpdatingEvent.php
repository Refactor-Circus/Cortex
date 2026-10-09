<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The ToolDescriptionModel `updating` Eloquent event.
 */
final class ToolDescriptionUpdatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ToolDescriptionModel $description) {}

    public function model(): Model
    {
        return $this->description;
    }

    public function hook(): string
    {
        return 'updating';
    }
}
