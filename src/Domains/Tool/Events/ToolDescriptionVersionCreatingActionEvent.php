<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\Tool\Data\CreateToolDescriptionVersionData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A new version of a tool's description override is about to be created.
 */
final class ToolDescriptionVersionCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $tool,
        public CreateToolDescriptionVersionData $data,
    ) {}
}
