---
description: "Cortex's optional surfaces and their strict enabled flags: cortex.ui.enabled (dashboard, path/middleware/gate from Atrium config), cortex.mcp.web.enabled and cortex.mcp.local.enabled; new surfaces use cortex.<surface>.enabled."
tags: [cortex, config, service-providers, mcp, ui]
kind: convention
---
# Cortex Optional Surfaces

Complements [[service-providers]]: these are the surfaces Cortex can switch off and the config keys that drive them.

## Surfaces

| Surface | Flag | Wiring from config |
|---|---|---|
| Dashboard UI | `cortex.ui.enabled` | path, middleware and gate come from Atrium's config |
| MCP web transport | `cortex.mcp.web.enabled` | `cortex.mcp.web.route`, `cortex.mcp.web.middleware` |
| MCP local transport | `cortex.mcp.local.enabled` | `cortex.mcp.local.handle` |

## Adding one

- A new optional surface gets a `cortex.<surface>.enabled` flag with the same strict check and config-driven wiring.
