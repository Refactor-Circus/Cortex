<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Support;

use RefactorCircus\Cortex\Domains\Tool\Concerns\HasVersionedDescription;
use Laravel\Mcp\Server\Tool as McpTool;

/**
 * Base class for tools that work everywhere Cortex looks: register the class
 * on a Laravel MCP server, in the `cortex.tools` config, or at runtime via
 * the ToolRegistry — agents receive it wrapped automatically. The description
 * served in every context is the published Cortex version when one exists,
 * falling back to the code-declared description otherwise.
 */
abstract class Tool extends McpTool
{
    use HasVersionedDescription;
}
