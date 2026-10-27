# Standards for Virtual and concrete MCP servers

> spec.md covers only the concrete server rename and tool override (D-013). Applicable to spec.md (read these): cortex/domains, architecture/domain-modules, architecture/naming, cortex/actions, actions/actions-events, actions/domain-guards, data/data-objects, models/models (the `tools` column and cast only), models/migrations (the `tools` migration only), models/stored-identifiers (morph aliases), cortex/publication-cache, http/authorization (`updateTools`), cortex/http-requests, http/request-persist, http/resources (four new fields), http/routes (two routes), cortex/mcp-tools, cortex/mcp-model-binding (`ServerMcpRequest`), cortex/mcp-responses, cortex/mcp-schemas, cortex/mcp-http-parity, cortex/atrium-screens (the tool editor), testing/arch-tests, testing/test-layers, cortex/testbench-environment, architecture/configuration (D-044: the warning rate-limit TTL is a new `cortex` config key with a documented banner and an int-cast hard default; the remap of AC 7 is the other config touch).
>
> Not applicable to spec.md (follow-up spec only, skip them): cortex/models (HasUlids), models/lifecycle-events (no new models), cortex/ulid-keys, cortex/immutable-versions, cortex/publish-pointer, cortex/instruction-resolution, cortex/sub-agents, cortex/runtime-exceptions, errors/exceptions (no new exceptions), cortex/optional-surfaces, architecture/service-providers. In the table below, the Why column of these rows describes the follow-up spec's use, not this spec's.

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| cortex/domains | docs/agent/standards/cortex/domains.md | local | Adds `VirtualServer`, renames `McpServer` to `ConcreteServer`; stored names kept (D-003, D-004) |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | New domain layout and provider; morph aliases for moved models |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | `{Entity}Model`, `{Verb}{Entity}Action`, requests, events for both domains |
| cortex/actions | docs/agent/standards/cortex/actions.md | local | Every new operation is one action wrapped by an HTTP and an MCP request |
| actions/actions-events | vendor/refactor-circus/standards/standards/actions/actions-events.md | refactor-circus/standards@dev-main | Starting/finished events, writes in one transaction |
| actions/domain-guards | vendor/refactor-circus/standards/standards/actions/domain-guards.md | refactor-circus/standards@dev-main | Tool and agent existence guards as field-keyed validation |
| data/data-objects | vendor/refactor-circus/standards/standards/data/data-objects.md | refactor-circus/standards@dev-main | `{Action}Data` inputs with `rules()`, `fromValidated()` in requests |
| models/models | vendor/refactor-circus/standards/standards/models/models.md | refactor-circus/standards@dev-main | New models, tables from `cortex.tables`, policy per model |
| cortex/models | docs/agent/standards/cortex/models.md | local | [follow-up only] Final models, `HasUlids`, explicit `$fillable` |
| models/lifecycle-events | vendor/refactor-circus/standards/standards/models/lifecycle-events.md | refactor-circus/standards@dev-main | [follow-up only] `{Entity}{Hook}Event` for every hook on the new models |
| models/migrations | vendor/refactor-circus/standards/standards/models/migrations.md | refactor-circus/standards@dev-main | New virtual server tables and the `tools` column on `mcp_instructions` |
| cortex/ulid-keys | docs/agent/standards/cortex/ulid-keys.md | local | [follow-up only] ULID keys on the new tables |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | Applies to spec.md for the warning TTL key (D-044). Follow-up only: new `cortex.tables` keys, `cortex.mcp.virtual` section with banner |
| models/stored-identifiers | vendor/refactor-circus/standards/standards/models/stored-identifiers.md | refactor-circus/standards@dev-main | Servers and agents referenced by slug or registered name; morph aliases on move |
| cortex/immutable-versions | docs/agent/standards/cortex/immutable-versions.md | local | [follow-up only] Virtual server instruction versions are immutable |
| cortex/publish-pointer | docs/agent/standards/cortex/publish-pointer.md | local | [follow-up only] FK-free `published_version_id` on virtual servers |
| cortex/publication-cache | docs/agent/standards/cortex/publication-cache.md | local | Published virtual server instructions and concrete server tool overrides are cached and invalidated |
| cortex/instruction-resolution | docs/agent/standards/cortex/instruction-resolution.md | local | [follow-up only] Unpublished virtual server is never cached and throws |
| cortex/sub-agents | docs/agent/standards/cortex/sub-agents.md | local | [follow-up only] Agents exposed as tools reuse the agent-as-tool build and its runtime cycle guard |
| cortex/runtime-exceptions | docs/agent/standards/cortex/runtime-exceptions.md | local | [follow-up only] `VirtualServerNotPublishedException` joins the runtime exceptions |
| errors/exceptions | vendor/refactor-circus/standards/standards/errors/exceptions.md | refactor-circus/standards@dev-main | [follow-up only] New exceptions on `CortexException` with `forX()` constructors |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | Policies for the new models; versions defer to their server |
| cortex/http-requests | docs/agent/standards/cortex/http-requests.md | local | New HTTP requests ask the Gate as user or guest |
| http/request-persist | vendor/refactor-circus/standards/standards/http/request-persist.md | refactor-circus/standards@dev-main | One request per operation, `persist()` calls one action |
| http/resources | vendor/refactor-circus/standards/standards/http/resources.md | refactor-circus/standards@dev-main | Virtual server resources with `schema()`, shared by HTTP and MCP |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | `virtual-servers` routes in the domain's `routes.php` |
| cortex/mcp-tools | docs/agent/standards/cortex/mcp-tools.md | local | New Cortex tools listed in `CortexServer::TOOLS` |
| cortex/mcp-model-binding | docs/agent/standards/cortex/mcp-model-binding.md | local | `VirtualServerMcpRequest` base resolves the slug |
| cortex/mcp-responses | docs/agent/standards/cortex/mcp-responses.md | local | Structured responses from the shared resources |
| cortex/mcp-schemas | docs/agent/standards/cortex/mcp-schemas.md | local | Described schema fields on the new tools |
| cortex/mcp-http-parity | docs/agent/standards/cortex/mcp-http-parity.md | local | Parity tests for every new resource-returning tool |
| cortex/optional-surfaces | docs/agent/standards/cortex/optional-surfaces.md | local | [follow-up only] Virtual server transport behind `cortex.mcp.virtual.enabled` (D-007) |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | [follow-up only] Optional route registered with a strict `=== true` check |
| cortex/atrium-screens | docs/agent/standards/cortex/atrium-screens.md | local | Screens for virtual servers and the concrete server tool override |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | ArchTest covers the new domain |
| testing/test-layers | docs/agent/standards/testing/test-layers.md | local | Behavior tested at the action, surfaces test wiring |
| cortex/testbench-environment | docs/agent/standards/cortex/testbench-environment.md | local | RenamedTables mode re-runs the new flows |
