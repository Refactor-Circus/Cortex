---
description: "Cortex's domains (VirtualAgent, ConcreteAgent, Tool, ConcreteServer, RedirectDomain), its Mcp/{Tools,Requests} domain subdirectory, top-level package-wide classes, and the stored names it must keep."
tags: [cortex, domains, layout, mcp]
kind: architectural
---
# Cortex Domains

Complements [[domain-modules]]: this is the list of Cortex's domains and the Cortex-only additions to the layout.

## Domains

- `VirtualAgent`: virtual agents, their versions, runtime and providers, and the package-wide reads a UI needs: agent search (`search.index`), runnable agents (`runnable-agents.index`) and the settings summary (`settings-summary.show`), beside `providers.index`.
- `ConcreteAgent`: class-based agents, their registry and overrides.
- `Tool`: the tool registry and tool description overrides.
- `ConcreteServer`: class-based (concrete) MCP servers, their registry, instruction overrides and tool overrides. It was called `McpServer`; its class stems (`McpInstruction*`, `McpServer*`) keep that name, and only the namespace and `ConcreteServerServiceProvider` changed.
- `RedirectDomain`: stored OAuth redirect domains and the dynamic client registration controller that accepts them.

## Extra domain subdirectory

- `Mcp/{Tools,Requests}/` holds a domain's MCP tools and their request classes; see [[mcp-model-binding]] and [[mcp-responses]].
- MCP tools are named `{Verb}{Entity}Tool` (`CreateVirtualAgentTool`).

## Top level

- Package-wide: `Cortex`, `CortexServiceProvider`, `Facades\Cortex`, the base `Http\Request` and `Mcp\Request`, `Mcp\CortexServer`, `Mcp\Tools\ListCortexHistoryTool`, `Http\Resources\AgentRunResource`, and `Support\` (`PublicationCache`, `WarningLimiter`, `AiToolAsMcpTool`, `Policies\Policy`).
- `CortexServiceProvider` also keeps the `Cortex` singleton, the translations, the Cortex MCP server and the history route (`loadHistoryRoutes()`).
- Cortex has no UI layer: no views, screens or UI controllers. What a UI needs is an endpoint with an MCP tool in a domain, as the three `VirtualAgent` reads above are, never a top-level UI folder.

## Stored names

- The two `ConcreteServer` models keep their pre-rename class names (`RefactorCircus\Cortex\Domains\McpServer\Models\{Entity}`) as their morph aliases through `keepMorphAliases()` in `ConcreteServerServiceProvider`, so stored rows stay uniform. The other models are stored under their own class names.
- The pre-rename names of `Support\Server`, `Concerns\HasVersionedInstructions`, the two models and their two policies stay loadable through the lazy `ConcreteServer/shims.php` autoload fallback until the next major release. No other old name is aliased.
- Tools, servers and agents are stored by their registered names, never their class names.
