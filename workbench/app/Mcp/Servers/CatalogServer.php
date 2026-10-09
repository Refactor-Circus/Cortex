<?php

declare(strict_types=1);

namespace Workbench\App\Mcp\Servers;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use RefactorCircus\Cortex\Domains\McpServer\Support\Server;
use Workbench\App\Domains\Catalog\Tools\CheckInventoryTool;

/**
 * Demo MCP server that keeps the instructions declared here.
 */
#[Name('Catalog')]
#[Version('1.0.0')]
#[Instructions('Answer questions about the product catalog and stock levels.')]
final class CatalogServer extends Server
{
    /**
     * @var array<int, class-string>
     */
    protected array $tools = [
        CheckInventoryTool::class,
    ];
}
