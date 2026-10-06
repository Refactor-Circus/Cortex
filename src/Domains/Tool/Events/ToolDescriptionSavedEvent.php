<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Foundation\Contracts\ModelLifecycleEvent;

/**
 * The ToolDescriptionModel `saved` Eloquent event.
 */
final class ToolDescriptionSavedEvent implements ModelLifecycleEvent
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
        return 'saved';
    }
}
