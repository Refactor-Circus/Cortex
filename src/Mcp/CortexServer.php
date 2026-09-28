<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp;

use JayI\Cortex\Mcp\Tools\CreateConcreteAgentVersionTool;
use JayI\Cortex\Mcp\Tools\CreateServerInstructionVersionTool;
use JayI\Cortex\Mcp\Tools\CreateVirtualAgentTool;
use JayI\Cortex\Mcp\Tools\CreateVirtualAgentVersionTool;
use JayI\Cortex\Mcp\Tools\DeleteConcreteAgentOverrideTool;
use JayI\Cortex\Mcp\Tools\DeleteServerInstructionsTool;
use JayI\Cortex\Mcp\Tools\DeleteVirtualAgentTool;
use JayI\Cortex\Mcp\Tools\ListConcreteAgentsTool;
use JayI\Cortex\Mcp\Tools\ListConcreteAgentVersionsTool;
use JayI\Cortex\Mcp\Tools\ListServerInstructionVersionsTool;
use JayI\Cortex\Mcp\Tools\ListServersTool;
use JayI\Cortex\Mcp\Tools\ListToolsTool;
use JayI\Cortex\Mcp\Tools\ListVirtualAgentsTool;
use JayI\Cortex\Mcp\Tools\ListVirtualAgentVersionsTool;
use JayI\Cortex\Mcp\Tools\PublishConcreteAgentVersionTool;
use JayI\Cortex\Mcp\Tools\PublishServerInstructionVersionTool;
use JayI\Cortex\Mcp\Tools\PublishVirtualAgentVersionTool;
use JayI\Cortex\Mcp\Tools\RunConcreteAgentTool;
use JayI\Cortex\Mcp\Tools\RunVirtualAgentTool;
use JayI\Cortex\Mcp\Tools\ShowConcreteAgentTool;
use JayI\Cortex\Mcp\Tools\ShowServerInstructionsTool;
use JayI\Cortex\Mcp\Tools\ShowVirtualAgentTool;
use JayI\Cortex\Mcp\Tools\ShowVirtualAgentVersionTool;
use JayI\Cortex\Mcp\Tools\UpdateConcreteAgentToolsTool;
use JayI\Cortex\Mcp\Tools\UpdateVirtualAgentTool;
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
     * @var array<class-string<ToolSearch>, array<int, class-string<Tool>|Tool>>
     */
    protected array $tools = [
        ToolSearch::class => [
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
        ],
    ];
}
