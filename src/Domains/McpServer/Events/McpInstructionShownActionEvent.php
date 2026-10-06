<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Foundation\Contracts\ActionFinishedEvent;

/**
 * An MCP server's instructions override was shown, with its published version loaded.
 */
final class McpInstructionShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public McpInstructionModel $instruction,
    ) {}
}
