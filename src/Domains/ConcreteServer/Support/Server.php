<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Support;

use Laravel\Mcp\Server as McpServer;
use RefactorCircus\Cortex\Domains\ConcreteServer\Concerns\HasCortexOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Concerns\HasVersionedInstructions;

/**
 * Base class for MCP servers that Cortex manages: register the class with
 * the McpServerRegistry (built in for Cortex's own server, via the
 * `cortex.mcp.servers` config, or at runtime) and clients are served the
 * published Cortex instructions when a version exists, and the stored tool
 * override when one is saved, falling back to what the class declares in
 * code otherwise.
 *
 * It uses the trait by its kept name, which brings in
 * {@see HasCortexOverrides}, so the base class and the servers that use
 * either trait serve the same overrides.
 */
abstract class Server extends McpServer
{
    use HasVersionedInstructions;
}
