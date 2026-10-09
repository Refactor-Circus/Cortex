<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A version of a tool's description override is about to be published.
 */
final class ToolDescriptionVersionPublishingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ToolDescriptionModel $description,
        public int $version,
    ) {}
}
