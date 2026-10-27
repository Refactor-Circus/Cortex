# Ticket draft (not yet created on GitHub)

**Title:** Virtual and concrete MCP servers

## Problem

Agents come in two kinds: virtual agents live entirely in the database (versioned
instructions, a tool list, sub-agents, published version runs), and concrete agents
are classes whose prompt and toolset Cortex can override. MCP servers have only half
of that: the `McpServer` domain registers class-based servers and overrides their
instructions. There is no way to define a server in the database, and a class-based
server's tool list cannot be changed without code.

## Desired outcome

- **Virtual servers**: an MCP server defined entirely in Cortex — name, slug,
  description, versioned instructions (immutable versions, the published one is
  served), and a list of exposed tools drawn from the tool registry plus
  virtual/concrete agents exposed as tools. Served over MCP at its own route.
  CRUD, versions and publish through actions, HTTP API, Cortex MCP tools and Atrium
  screens, like virtual agents.
- **Concrete servers**: class-based servers (today's `McpServer` domain) gain the
  same overrides concrete agents have — instructions (already there) plus a
  replaceable tool list — and line up with `ConcreteAgent` in naming and shape:
  the `McpServer` domain is renamed `ConcreteServer` (stored names and morph
  aliases kept), and virtual servers get a new `VirtualServer` domain.

## Acceptance hints

- Virtual server create/update/delete/list/show, versions create/list/show/publish:
  one action each, HTTP request + MCP request on the same action, parity tests.
- An unpublished virtual server is not served (like `VirtualAgentNotPublishedException`).
- Concrete server tool override: a non-null list replaces the code-declared tools;
  delete override restores code defaults.
- Tables via `cortex.tables`, policies per model, model events, ArchTest green,
  RenamedTables mode covers the new flows.
- Atrium screens for both kinds.

## Out of scope

- Proxying or aggregating remote (third-party) MCP servers.
- MCP resources and prompts on virtual servers (tools only) unless decided otherwise.
