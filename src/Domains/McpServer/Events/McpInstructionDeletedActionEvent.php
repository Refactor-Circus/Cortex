<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * An MCP server's instructions override was deleted; the server falls back to its code-declared instructions.
 */
final class McpInstructionDeletedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public McpInstructionModel $instruction,
    ) {}
}
