<?php

declare(strict_types=1);

namespace Workbench\App\Mcp\Servers;

use JayI\Cortex\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Workbench\App\Domains\Orders\Tools\LookupOrderTool;
use Workbench\App\Domains\Support\Tools\CreateTicketTool;
use Workbench\App\Domains\Support\Tools\SearchKnowledgeBaseTool;

/**
 * Demo MCP server whose instructions Cortex overrides.
 */
#[Name('Support')]
#[Version('1.0.0')]
#[Instructions('Help customers with orders and support questions. Search the knowledge base before opening a ticket.')]
final class SupportServer extends Server
{
    /**
     * @var array<int, class-string>
     */
    protected array $tools = [
        LookupOrderTool::class,
        SearchKnowledgeBaseTool::class,
        CreateTicketTool::class,
    ];
}
