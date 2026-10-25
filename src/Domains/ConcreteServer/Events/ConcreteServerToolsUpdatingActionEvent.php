<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A concrete MCP server's tool list is about to be overridden.
 */
final class ConcreteServerToolsUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $server,
        public UpdateConcreteServerToolsData $data,
    ) {}
}
