<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Foundation\Contracts\ActionStartingEvent;

/**
 * The versions of a tool's description override are about to be listed.
 */
final class ToolDescriptionVersionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ToolDescriptionModel $description,
    ) {}
}
