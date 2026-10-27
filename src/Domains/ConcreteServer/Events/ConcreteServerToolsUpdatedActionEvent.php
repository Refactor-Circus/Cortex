<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A concrete MCP server's tool list was overridden; the override row holds the stored list.
 */
final class ConcreteServerToolsUpdatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public McpInstructionModel $instruction,
    ) {}
}
