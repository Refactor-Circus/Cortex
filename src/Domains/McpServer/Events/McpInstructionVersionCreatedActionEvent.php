<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A new version of an MCP server's instructions override was created, and published when asked.
 */
final class McpInstructionVersionCreatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $server,
        public McpInstructionVersionModel $version,
    ) {}
}
