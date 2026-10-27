---
description: "Every Cortex HTTP endpoint and MCP tool reaches its behavior through one action; a new operation starts with the action, then gets its HTTP request and its MCP request wrapping the same action."
tags: [cortex, actions, http, mcp]
kind: convention
---
# Actions Behind Both Surfaces

Complements [[actions-events]]: Cortex has two surfaces, HTTP and MCP, and both run on the same actions.

## Rules

- All behavior flows through an action in `src/Domains/{Domain}/Actions/`. No HTTP request ([[request-persist]]) or MCP request ([[mcp-tools]]) contains business logic of its own.
- The HTTP request and the MCP request for an operation call the same action with the same `{Action}Data`, so the two surfaces never drift.
- A new operation is built action first, then its HTTP request and its MCP request; parity is tested per [[mcp-http-parity]].
