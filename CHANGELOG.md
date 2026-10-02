# Release Notes

## [Unreleased](https://github.com/jayi/cortex/compare/v0.1.0...1.x)

### Added

- Virtual agents: DB-backed agents (`cortex_virtual_agents`) that own their prompt, versioned in place with immutable versions and a published-version pointer (`cortex_virtual_agent_versions`), plus registered tools, provider/model settings, virtual sub-agents (`cortex_virtual_agent_sub_agents`, with cycle protection) and concrete sub-agents. Updating the instructions saves a new published version. All Cortex tables use ULID primary keys.
- Concrete agents: `AgentRegistry` registers class-based `Laravel\Ai\Contracts\Agent` implementations by name via config (`cortex.agents`) or `Cortex::agents()->register()`. Registered agents are listed, runnable and attachable as sub-agents of virtual agents.
- `JayI\Cortex\Agents\Agent` base class and `HasCortexOverrides` trait: agents declare `defaultInstructions()` and `defaultTools()`, and run with the published Cortex overrides (`cortex_concrete_agent_overrides`, `cortex_concrete_agent_override_versions`) when they exist — a versioned prompt override and a toolset override picked from the class's own tools and registered Cortex tools. `#[LockedTools]` opts an agent out of toolset overrides.
- `DbAgent` implements `CanActAsTool`, so a virtual sub-agent is offered to its parent under its slug and description instead of `DbAgent`.
- `ToolRegistry` for registering `Laravel\Ai\Contracts\Tool` classes by name via config (`cortex.tools`) or `Cortex::tools()->register()`.
- Tool tags: tools are tagged at registration (`Cortex::tools()->register($name, $class, $tags)` or a `['class' => ..., 'tags' => [...]]` config entry) and from namespace patterns (`cortex.tool_tags.namespaces`). The dashboard tool list filters by tag, the virtual and concrete agent tool pickers filter by tag, name and selection, and `GET /cortex/tools` and `list-tools-tool` take a `tag` filter and return each tool's `tags`.
- Agent execution on the Laravel AI SDK: `Cortex::runVirtualAgent()`, `Cortex::runConcreteAgent()`, `Cortex::virtualAgent()`, `Cortex::concreteAgent()`, `POST /cortex/virtual-agents/{slug}/run`, `POST /cortex/concrete-agents/{agent}/run`, and the matching MCP tools.
- REST API under the configurable `cortex` prefix covering virtual agents and their prompt versions, concrete agents and their overrides, tools, and run.
- `CortexServer` MCP server with tools at parity with the API, config-gated web (`Mcp::web`) and local (`Mcp::local`) transports, disabled by default.
- `laravel/ai` (^0.11) and `laravel/mcp` (^1.0) dependencies.
- MCP server instruction management with immutable versioning and a published-version pointer (`cortex_mcp_instructions`, `cortex_mcp_instruction_versions`): published overrides replace a server's code-declared `#[Instructions]` at runtime.
- `McpServerRegistry` for registering MCP server classes by name via config (`cortex.mcp.servers`) or `Cortex::servers()->register()`; Cortex's own server is always registered as `cortex`.
- `JayI\Cortex\Mcp\Server` base class and `HasVersionedInstructions` trait so any Laravel MCP server can serve its published instruction override.
- REST endpoints under `/cortex/servers` for listing servers and managing instruction overrides, six matching MCP tools on `CortexServer` (25 tools in all), and a Servers section in the dashboard with a versioned instructions editor.

- Model events: every Eloquent hook of every Cortex model dispatches its own class in `JayI\Cortex\Events\Model` (`{Model}{Hook}Event`, e.g. `VirtualAgentVersionCreatedEvent`) through the `DispatchesModelEvents` trait. All implement `JayI\Cortex\Contracts\ModelLifecycleEvent`.
- Action events: every action dispatches a start event before its work and a finish event with its result (`JayI\Cortex\Events\Action`, e.g. `VirtualAgentRunningActionEvent` / `VirtualAgentRanActionEvent`). Start events implement `ActionStartingEvent`. Finish events implement `ActionFinishedEvent`, dispatch after commit and are skipped when the action throws. See `docs/events.md`.
- Policies for every model (`JayI\Cortex\Policies`), registered with the Gate from the new `cortex.policies` config. Every API endpoint and MCP tool that touches a model now authorizes through them, as the signed-in user or as a guest. The bundled policies allow everything, since Cortex records have no owner, so existing behaviour is unchanged. Version policies defer to their virtual agent or override through the Gate. See `docs/policies.md`.

- Dashboard permission gates: every Atrium page, action and control is checked against the `cortex.policies` policies exactly as the JSON API and MCP tools check them, through `JayI\Cortex\Http\Ui\ScreenAccess` and the new `@cortexCan` Blade conditional. Navigation items, buttons, forms and cards are shown only when their action would be allowed, the run page offers only the agents the user may `run`, and search returns only what the searcher may view.
- `JayI\Cortex\Features\CortexSupportFeature` (needs `jayi/pennantplus`, suggested) and the `cortex.atrium.features` config: switch Cortex in Atrium on and off as a whole. Feature classes that cannot be loaded are skipped.
- `JayI\Cortex\Atrium\Badges` maps each dashboard state to its Atrium colour.

### Changed

- The Atrium screens follow Atrium's screen conventions: actions, tabs and back links are icon buttons (the label is the tooltip and accessible name), states are status dots carrying `data-status`, and every navigation item has a Heroicons icon. Tool tag filters are linked badges. Requires `jayi/atrium` at f5eb488 or later.
- The dashboard pages now authorize: before, they ran actions without asking the policies the API asks.

- The dashboard is server-rendered Blade built on `jayi/atrium`, which Cortex now requires, replacing the Vue 3 SPA. Cortex registers an Atrium plugin with navigation, routes under `/atrium/cortex/...`, search over virtual and concrete agents, and a settings panel. Atrium owns the path, middleware and `viewAtrium` gate, so the `ui.auth` config, the `UiTokenResolver` contract and the `/cortex/ui` route are gone; `ui.enabled` remains as the switch.
- Requires `laravel/framework` instead of `illuminate/support`, since the package uses form requests, events and queues from the framework.

### Removed

- The TypeScript SDK (`@jayi/cortex-sdk`, `sdk/`), its npm workspace and `sdk:generate`/`sdk:build` scripts, and the `dedoc/scramble` dev dependency that exported its OpenAPI spec.
- The `cortex-assets` publish tag and the empty `public/` directory it published. The dashboard is Blade rendered through Atrium and ships no assets of its own.
- The skeleton `cortex:placeholder` Artisan command and the `cortex::messages.placeholder` translation.

### Fixed

- Agents calling an MCP tool whose `handle()` type-hints its own `Laravel\Mcp\Request` subclass now pass their arguments to it. Previously laravel/ai's `McpServerTool` bound the arguments only as the base request, so such tools, including Cortex's own MCP tools, received an empty request and failed validation.


## [v0.1.0](https://github.com/jayi/cortex/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
