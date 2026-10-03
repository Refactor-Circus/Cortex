<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionStartingEvent;

/**
 * The registered MCP servers are about to be listed.
 */
final class McpServersListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;
}
