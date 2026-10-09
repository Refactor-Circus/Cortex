<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Events;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * The versions of an MCP server's instructions override were listed.
 */
final class McpInstructionVersionsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Collection<int, McpInstructionVersionModel>  $versions
     */
    public function __construct(
        public McpInstructionModel $instruction,
        public Collection $versions,
    ) {}
}
