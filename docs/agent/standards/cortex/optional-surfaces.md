---
description: "Cortex's optional surfaces and their strict enabled flags: cortex.mcp.web.enabled and cortex.mcp.local.enabled; new surfaces use cortex.<surface>.enabled. Cortex has no UI surface."
tags: [cortex, config, service-providers, mcp]
kind: convention
---
# Cortex Optional Surfaces

Complements [[service-providers]]: these are the surfaces Cortex can switch off and the config keys that drive them.

## Surfaces

| Surface | Flag | Wiring from config |
|---|---|---|
| MCP web transport | `cortex.mcp.web.enabled` | `cortex.mcp.web.route`, `cortex.mcp.web.middleware` |
| MCP local transport | `cortex.mcp.local.enabled` | `cortex.mcp.local.handle` |

Cortex has no UI surface: a UI is another package that calls the JSON API.

## Adding one

- A new optional surface gets a `cortex.<surface>.enabled` flag with the same strict check and config-driven wiring.
