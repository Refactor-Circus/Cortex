# Standards for Cortex v2: API and MCP only

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | Three new named routes in `src/Domains/VirtualAgent/routes.php` inside the shared `cortex.` group |
| http/request-persist | vendor/refactor-circus/standards/standards/http/request-persist.md | refactor-circus/standards@dev-main | `SearchAgentsRequest`, `IndexRunnableAgentsRequest`, `ShowSettingsSummaryRequest`; controllers only return `persist()` |
| http/resources | vendor/refactor-circus/standards/standards/http/resources.md | refactor-circus/standards@dev-main | New final `AgentReferenceResource` and `SettingsSummaryResource` (model-less, in `Http/Resources`) |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | Search and runnable agents ask `viewAny`/`view`/`run` through the bundled policies |
| actions/actions-events | vendor/refactor-circus/standards/standards/actions/actions-events.md | refactor-circus/standards@dev-main | New read actions with their starting/finished event pairs, as Cortex's list actions have |
| data/data-objects | vendor/refactor-circus/standards/standards/data/data-objects.md | refactor-circus/standards@dev-main | `SearchAgentsData` with `rules()`; request builds it with `fromValidated()` |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | Names of the new actions, events, requests, resources, controllers and MCP tools |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | `src/Atrium` (top-level cross-domain layer) is deleted; new code goes in the VirtualAgent domain |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | No Atrium or PennantPlus reference left; Keystone the only suite `require`; Scramble dev-only |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | Removal of `cortex.ui` and `cortex.atrium`; routes banner names the OpenAPI file and client |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | `CortexServiceProvider` loses the Atrium plugin, screens, views, Blade directive and `cortex-views` tag |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | `tests/ArchTest.php` rule for Atrium changes to "nothing uses Atrium, PennantPlus or Scramble" |
| testing/test-layers | docs/agent/standards/testing/test-layers.md | local (overrides refactor-circus/standards@dev-main) | Behavior tested at the new actions; HTTP and MCP tests cover wiring only |
| cortex/actions | docs/agent/standards/cortex/actions.md | local | Each new operation is an action reached by both an HTTP request and an MCP request |
| cortex/domains | docs/agent/standards/cortex/domains.md | local | Drops `src/Atrium` from the layout; updated by this spec |
| cortex/http-requests | docs/agent/standards/cortex/http-requests.md | local | New requests ask the Gate as user or guest (401 guest / 403 user) |
| cortex/mcp-tools | docs/agent/standards/cortex/mcp-tools.md | local | Three new tools listed in `CortexServer::TOOLS`, delegating to McpRequests |
| cortex/mcp-responses | docs/agent/standards/cortex/mcp-responses.md | local | Structured `data` envelope built from the shared resources; empty lists as `data: []` |
| cortex/mcp-schemas | docs/agent/standards/cortex/mcp-schemas.md | local | Schema fields of the new tools carry descriptions |
| cortex/mcp-http-parity | docs/agent/standards/cortex/mcp-http-parity.md | local | Parity test for each new tool |
| cortex/optional-surfaces | docs/agent/standards/cortex/optional-surfaces.md | local | `cortex.ui.enabled` surface removed; standard updated by this spec |
| cortex/atrium-screens | docs/agent/standards/cortex/atrium-screens.md | local | Deleted with the Atrium UI |
| cortex/testbench-environment | docs/agent/standards/cortex/testbench-environment.md | local | Provider order loses Atrium; Scramble export runs against the workbench |
