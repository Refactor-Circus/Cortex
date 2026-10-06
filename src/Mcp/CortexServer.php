<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp;

use JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\CreateConcreteAgentVersionTool;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\DeleteConcreteAgentOverrideTool;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\ListConcreteAgentsTool;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\ListConcreteAgentVersionsTool;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\PublishConcreteAgentVersionTool;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\RunConcreteAgentTool;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\ShowConcreteAgentTool;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\UpdateConcreteAgentToolsTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\CreateServerInstructionVersionTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\DeleteServerInstructionsTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\ListServerInstructionVersionsTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\ListServersTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\PublishServerInstructionVersionTool;
use JayI\Cortex\Domains\McpServer\Mcp\Tools\ShowServerInstructionsTool;
use JayI\Cortex\Domains\Tool\Mcp\Tools\ListToolsTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentVersionTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\DeleteVirtualAgentTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ListVirtualAgentsTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ListVirtualAgentVersionsTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\PublishVirtualAgentVersionTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\RunVirtualAgentTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentVersionTool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\UpdateVirtualAgentTool;
use JayI\Cortex\Mcp\Tools\ListCortexHistoryTool;
use JayI\Foundation\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;

#[Name('Cortex')]
#[Version('1.0.0')]
#[Instructions('Manage Cortex virtual agents, concrete agents, tools, and MCP server instructions. Virtual agents live in the database: each holds its own versioned prompt (content is immutable per version; the published version runs), registered tools, and optional virtual or concrete sub-agents, and runs with run-virtual-agent. Concrete agents are classes registered in code; their prompt and toolset can be overridden (prompt overrides are versioned and published), and they run with run-concrete-agent. Registered MCP servers hold versioned instruction overrides that replace their code-declared instructions when published.')]
final class CortexServer extends Server
{
    /**
     * Every tool the server offers, behind ToolSearch.
     *
     * @var array<int, class-string<Tool>>
     */
    public const array TOOLS = [
        // Virtual agents
        ListVirtualAgentsTool::class,
        CreateVirtualAgentTool::class,
        ShowVirtualAgentTool::class,
        UpdateVirtualAgentTool::class,
        DeleteVirtualAgentTool::class,
        RunVirtualAgentTool::class,

        // Virtual agent prompt versions
        ListVirtualAgentVersionsTool::class,
        CreateVirtualAgentVersionTool::class,
        ShowVirtualAgentVersionTool::class,
        PublishVirtualAgentVersionTool::class,

        // Concrete agents
        ListConcreteAgentsTool::class,
        ShowConcreteAgentTool::class,
        RunConcreteAgentTool::class,
        ListConcreteAgentVersionsTool::class,
        CreateConcreteAgentVersionTool::class,
        PublishConcreteAgentVersionTool::class,
        UpdateConcreteAgentToolsTool::class,
        DeleteConcreteAgentOverrideTool::class,

        // Tools
        ListToolsTool::class,

        // MCP servers
        ListServersTool::class,
        ShowServerInstructionsTool::class,
        ListServerInstructionVersionsTool::class,
        CreateServerInstructionVersionTool::class,
        PublishServerInstructionVersionTool::class,
        DeleteServerInstructionsTool::class,

        // History
        ListCortexHistoryTool::class,
    ];

    /**
     * @var array<class-string<ToolSearch>, array<int, class-string<Tool>|Tool>>
     */
    protected array $tools = [ToolSearch::class => self::TOOLS];
}
