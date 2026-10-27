# References

## VirtualAgent domain

- **Location:** `src/Domains/VirtualAgent/` (`Models/VirtualAgentModel.php`, `Models/VirtualAgentVersionModel.php`, `Actions/*`, `Mcp/`, `Http/`).
- **Relevance:** the template for a DB-defined, versioned, publishable entity.
- **Pattern to borrow:** name/slug/description row with an FK-free `published_version_id`; immutable versions; `tools` json of registered tool names; `concrete_sub_agents` json of concrete agent names; `virtual_agent_sub_agents` pivot for virtual agents; Create/Update/Delete/List/Show and Version Create/List/Show/Publish actions with HTTP and MCP requests; published content through `PublicationCache`.

## ConcreteAgent domain

- **Location:** `src/Domains/ConcreteAgent/` (`Models/ConcreteAgentOverrideModel.php`, `Actions/UpdateConcreteAgentToolsAction.php`, `Actions/DeleteConcreteAgentOverrideAction.php`, `Data/UpdateConcreteAgentToolsData.php`).
- **Relevance:** the template for overriding a class-based thing keyed by its registered name.
- **Pattern to borrow:** override row keyed by registered name with a nullable `tools` list (null = code defaults, non-null replaces them); `UpdateConcreteAgentToolsData::rules($agent)`; deleting the override restores defaults.

## McpServer domain (to be renamed ConcreteServer)

- **Location:** `src/Domains/McpServer/` (`Services/McpServerRegistry.php`, `Services/McpInstructionOverrides.php`, `Support/Server.php`, `Concerns/HasVersionedInstructions.php`, `Models/McpInstruction{,Version}Model.php`), tables `mcp_instructions` / `mcp_instruction_versions` in `src/Support/Tables.php` and `config/cortex.php`, migration `database/migrations/2026_01_01_000004_create_cortex_mcp_instruction_tables.php`.
- **Relevance:** the code that is renamed and gains the tool override.
- **Pattern to borrow:** the registry reads server classes by reflection without instantiating them; `Support\Server` applies the published override at serve time — the tool override hooks in the same place.

## Cortex MCP server and transports

- **Location:** `src/Mcp/CortexServer.php`, `config/cortex.php` (`mcp` section), README "MCP" section (`Mcp::web('/mcp/cortex', ...)`, `mcp:start cortex`).
- **Relevance:** how a server is exposed today (config-gated `Mcp::web`/`Mcp::local`, disabled by default) and where new Cortex tools are listed (`CortexServer::TOOLS`).

## Prior spec

- **Location:** `docs/agent/specs/2026-08-07-1231-mcp-server-instructions/`.
- **Relevance:** built the server instruction overrides; its decisions on registration and naming carry over.
