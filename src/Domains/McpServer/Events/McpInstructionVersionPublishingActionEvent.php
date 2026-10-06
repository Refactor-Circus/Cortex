<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Foundation\Contracts\ActionStartingEvent;

/**
 * A version of an MCP server's instructions override is about to be published.
 */
final class McpInstructionVersionPublishingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public McpInstructionModel $instruction,
        public int $version,
    ) {}
}
