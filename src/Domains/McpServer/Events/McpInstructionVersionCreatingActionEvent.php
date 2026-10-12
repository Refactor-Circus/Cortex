<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\McpServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A new version of an MCP server's instructions override is about to be created.
 */
final class McpInstructionVersionCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $server,
        public CreateMcpInstructionVersionData $data,
    ) {}
}
