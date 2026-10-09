<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * The versions of an MCP server's instructions override are about to be listed.
 */
final class McpInstructionVersionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public McpInstructionModel $instruction,
    ) {}
}
