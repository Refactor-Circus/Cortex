<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Support;

use Laravel\Mcp\Server as McpServer;
use RefactorCircus\Cortex\Domains\McpServer\Concerns\HasVersionedInstructions;

/**
 * Base class for MCP servers whose instructions Cortex manages: register the
 * class with the McpServerRegistry (built in for Cortex's own server, via
 * the `cortex.mcp.servers` config, or at runtime) and the instructions
 * served to clients are the published Cortex version when one exists,
 * falling back to the code-declared instructions otherwise.
 */
abstract class Server extends McpServer
{
    use HasVersionedInstructions;
}
