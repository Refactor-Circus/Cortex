<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * An MCP server's instructions override is about to be deleted.
 */
final class McpInstructionDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public McpInstructionModel $instruction,
    ) {}
}
