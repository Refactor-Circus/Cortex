<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\ClearConcreteServerToolsData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A concrete MCP server's tool override is about to be cleared, restoring its code tools.
 */
final class ConcreteServerToolsClearingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $server,
        public ClearConcreteServerToolsData $data,
    ) {}
}
