<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Foundation\Contracts\ActionStartingEvent;

/**
 * A tool's description override is about to be deleted.
 */
final class ToolDescriptionDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ToolDescriptionModel $description,
    ) {}
}
