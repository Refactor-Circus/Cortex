<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Mcp;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\CreateConcreteAgentVersionTool;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\DeleteConcreteAgentOverrideTool;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\ListConcreteAgentsTool;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\ListConcreteAgentVersionsTool;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\PublishConcreteAgentVersionTool;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\RunConcreteAgentTool;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\ShowConcreteAgentTool;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\UpdateConcreteAgentToolsTool;
use RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\CreateServerInstructionVersionTool;
use RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\DeleteServerInstructionsTool;
use RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\ListServerInstructionVersionsTool;
use RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\ListServersTool;
use RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\PublishServerInstructionVersionTool;
use RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\ShowServerInstructionsTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\CreateRedirectDomainTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\DeleteRedirectDomainTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\ListRedirectDomainsTool;
use RefactorCircus\Cortex\Domains\Tool\Mcp\Tools\ListToolsTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentVersionTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\DeleteVirtualAgentTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ListVirtualAgentsTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ListVirtualAgentVersionsTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\PublishVirtualAgentVersionTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\RunVirtualAgentTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentVersionTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\UpdateVirtualAgentTool;
use RefactorCircus\Cortex\Mcp\Tools\ListCortexHistoryTool;
use RefactorCircus\Keystone\Mcp\Server;

#[Name('Cortex')]
#[Version('1.0.0')]
#[Instructions('Manage Cortex virtual agents, concrete agents, tools, and MCP server instructions. Virtual agents live in the database: each holds its own versioned prompt (content is immutable per version; the published version runs), registered tools, and optional virtual or concrete sub-agents, and runs with run-virtual-agent. Concrete agents are classes registered in code; their prompt and toolset can be overridden (prompt overrides are versioned and published), and they run with run-concrete-agent. Registered MCP servers hold versioned instruction overrides that replace their code-declared instructions when published. Redirect domains are the origins MCP clients may register OAuth redirect URIs on, beside the configured ones.')]
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

        // OAuth redirect domains
        ListRedirectDomainsTool::class,
        CreateRedirectDomainTool::class,
        DeleteRedirectDomainTool::class,

        // History
        ListCortexHistoryTool::class,
    ];

    /**
     * @var array<class-string<ToolSearch>, array<int, class-string<Tool>|Tool>>
     */
    protected array $tools = [ToolSearch::class => self::TOOLS];
}
