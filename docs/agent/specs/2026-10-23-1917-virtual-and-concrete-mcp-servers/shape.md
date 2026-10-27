# Virtual and concrete MCP servers — Shaping Notes

Ticket: [#9](https://github.com/Refactor-Circus/Cortex/issues/9).

## Scope

> spec.md covers only the concrete server half (rename and tool override, D-013). The virtual server half below, and D-002, D-007, D-008, D-010, D-014 and D-015, belong to the follow-up spec.

Give MCP servers the same two kinds agents have.

- **Virtual servers** (new `VirtualServer` domain): an MCP server defined entirely in
  the database — name, slug, description, versioned instructions (immutable versions,
  the published one is served) and the tools it exposes: registered tools plus
  virtual and concrete agents exposed as tools (D-002). Served over MCP. CRUD,
  versions and publish through one action each, with an HTTP request and an MCP
  request on the same action, parity tests and Atrium screens, like virtual agents.
  An unpublished virtual server is not served.
- **Concrete servers** (today's `McpServer` domain, renamed `ConcreteServer`, D-003):
  class-based servers keep their instruction overrides and gain a replaceable tool
  list — a non-null list replaces the code-declared tools, deleting the override
  restores the code defaults — lined up with `ConcreteAgent` in naming and shape.
  Stored names (table keys, morph aliases, registered server names) stay.

Out of scope: proxying or aggregating remote MCP servers; MCP resources and prompts
on virtual servers (tools only).

## Decisions

- D-001: create the GitHub issue as drafted (#9).
- D-002: a virtual server exposes registered tools plus virtual/concrete agents as tools.
- D-003: rename `McpServer` to `ConcreteServer` (keep stored names and morph aliases)
  and add a `VirtualServer` domain.
- D-004: the rename changes PHP classes and namespaces only; table keys, HTTP
  routes and names, MCP tool names, config keys and registered server names stay
  (no breaking change).
- D-005: a concrete server's tool override is a new nullable `tools` column on the
  existing `mcp_instructions` override row (new migration), mirroring
  `ConcreteAgentOverrideModel`.
- D-006: the standards list below is confirmed as proposed.
- D-007: virtual servers are served by one config-gated route with the server slug
  in the path (`cortex.mcp.virtual.enabled`, off by default), resolved per request.
- D-008: a virtual server's tool and agent lists live on the server row, like
  virtual agents (`tools` json, `concrete_agents` json, pivot to virtual agents);
  only instructions are versioned.

## Context

- **References**: see `references.md`. The closest patterns are the `VirtualAgent`
  domain (DB-defined, versioned, published pointer, `tools` json, sub-agent pivot,
  `concrete_sub_agents` json) and the `ConcreteAgent` domain (override row keyed by
  registered name, nullable `tools` list replacing code defaults,
  `UpdateConcreteAgentToolsAction`, `DeleteConcreteAgentOverrideAction`). The current
  `McpServer` domain (registry, `McpInstructionOverrides`, `Support\Server`,
  `HasVersionedInstructions`) is what gets renamed and extended. Prior spec
  `2026-08-07-1231-mcp-server-instructions` built the instruction overrides.
- **Visuals**: none provided; Atrium screens follow the existing virtual agent and
  concrete agent/server screens.
- **Product alignment**: `docs/agent/product/` is empty; nothing to align with.
- No `keepMorphAliases()` call exists in `src/` today, although `cortex/domains`
  describes one: the rename is the first move that needs aliases (the `McpServer`
  models' old classes), so it likely adds the first call.

## Standards

Confirmed (D-006). spec.md covers only the concrete half (D-013). Lines that describe virtual servers (new `VirtualServer` domain, ULID keys, versions and publish pointer, `VirtualServerNotPublishedException`, sub-agents, the virtual MCP transport and its service-provider flag) are follow-up only; `architecture/configuration` applies to spec.md for the warning TTL key (D-044); see `standards.md` for the exact split.

- `cortex/domains` — adds `VirtualServer`, renames `McpServer`; stored names kept.
- `architecture/domain-modules` — new domain layout and provider; morph aliases for moved models.
- `architecture/naming` — `{Entity}Model`, `{Verb}{Entity}Action`, requests, events for both domains.
- `cortex/actions` — every new operation starts as one action wrapped by HTTP and MCP requests.
- `actions/actions-events` — starting/finished events, writes in one transaction.
- `actions/domain-guards` — tool/agent existence and cycle guards as field-keyed validation.
- `data/data-objects` — `{Action}Data` inputs with `rules()`, `fromValidated()` in requests.
- `models/models`, `cortex/models`, `models/lifecycle-events` — new models, policies, model events.
- `models/migrations`, `cortex/ulid-keys`, `architecture/configuration` — new tables via `cortex.tables`, ULID keys.
- `models/stored-identifiers` — reference servers/agents by slug or registered name; morph alias on move.
- `cortex/immutable-versions`, `cortex/publish-pointer`, `cortex/publication-cache`, `cortex/instruction-resolution` — virtual server versions, publish pointer and cached published instructions.
- `cortex/sub-agents` — agents exposed as tools on a virtual server reuse the agent-as-tool build and its cycle guards.
- `cortex/runtime-exceptions`, `errors/exceptions` — `VirtualServerNotPublishedException` and not-found exceptions.
- `http/authorization`, `cortex/http-requests`, `http/request-persist`, `http/resources`, `http/routes` — HTTP API.
- `cortex/mcp-tools`, `cortex/mcp-model-binding`, `cortex/mcp-responses`, `cortex/mcp-schemas`, `cortex/mcp-http-parity` — Cortex MCP tools for both kinds.
- `cortex/optional-surfaces`, `architecture/service-providers` — virtual server MCP transport behind a strict enabled flag.
- `cortex/atrium-screens` — screens for both kinds.
- `testing/arch-tests`, `testing/test-layers`, `cortex/testbench-environment` — ArchTest, test layering, RenamedTables mode.
