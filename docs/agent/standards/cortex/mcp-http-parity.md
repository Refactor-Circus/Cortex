---
description: "Every MCP tool that returns a resource has a parity test: its structured content equals the canonical HTTP payload; list tools also test the empty data [] case; list-cortex-history-tool is exempt."
tags: [cortex, testing, mcp, parity]
kind: convention
---
# MCP/HTTP Parity

Complements [[test-layers]]: in Cortex, every MCP tool that returns a resource has a test proving its structured content equals the HTTP payload for the same record.

## Rules

- HTTP is the canonical shape; MCP must match. This is the executable check on the shared-resource contract ([[resources]]).
- Required for every mutation tool; list tools also test the empty case (`data: []` must not error).
- `list-cortex-history-tool` is exempt: it returns audit entries, not a resource, and is tested against the history route's not-installed answer instead.
- MCP tests live in `tests/Feature/Mcp/` and invoke tools through `mcpTool(ToolClass::class, $args)` (Keystone's `Testing\McpTools::call()`, which also checks the answer against the tool's output schema), never by constructing requests by hand.

```php
$http = $this->getJson(route('cortex.tools.index', ['tag' => 'orders']))->json('data');

mcpTool(ListToolsTool::class, ['tag' => 'orders'])
    ->assertOk()
    ->assertStructuredContent(['data' => $http]);
```
