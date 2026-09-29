<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionStartingEvent;

/**
 * The registered tools are about to be listed, optionally only those
 * carrying one tag.
 */
final class ToolsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ?string $tag = null,
    ) {}
}
