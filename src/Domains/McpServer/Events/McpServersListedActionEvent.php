<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionFinishedEvent;

/**
 * The registered MCP servers were listed.
 */
final class McpServersListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<array<string, mixed>>  $servers
     */
    public function __construct(
        public array $servers,
    ) {}
}
