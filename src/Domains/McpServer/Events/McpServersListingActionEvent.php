<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Foundation\Contracts\ActionStartingEvent;

/**
 * The registered MCP servers are about to be listed.
 */
final class McpServersListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;
}
