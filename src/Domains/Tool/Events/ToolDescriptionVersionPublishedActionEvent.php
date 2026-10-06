<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Foundation\Contracts\ActionFinishedEvent;

/**
 * A version of a tool's description override was published; `$description->publishedVersion` is the new one.
 */
final class ToolDescriptionVersionPublishedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ToolDescriptionModel $description,
    ) {}
}
