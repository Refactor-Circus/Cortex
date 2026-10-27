---
description: Per-model abstract McpRequest bases resolve the identifier in the tool input to a model, memoized with ??= and loaded with firstOrFail().
tags: [cortex, mcp, requests, binding]
kind: convention
---
# MCP Model Binding

Identifier-to-model resolution for MCP tools lives in an abstract per-model request base, the MCP analog of route-model binding.

## The base

- Every lookup from tool input to a model goes through a per-model base (`ConcreteAgentMcpRequest`, `ServerMcpRequest`), even when only one tool needs it: consistency over YAGNI.
- Memoize with `??=`; rules and `handle()` may both need the model.
- Use `firstOrFail()`. The base `persist()` catches `ModelNotFoundException` and answers `Not found.`, so tools never check existence by hand. An unknown registered name throws `ModelNotFoundException` on purpose to get the same answer.
- The base also offers the identifier rules (`agentRules()`, `versionRules()`); concrete requests add them alongside the input rules ([[data-objects]]).

```php
abstract class ConcreteAgentMcpRequest extends Request
{
    private ?ConcreteAgentOverrideModel $override = null;

    protected function override(): ConcreteAgentOverrideModel
    {
        return $this->override ??= ConcreteAgentOverrideModel::query()
            ->where('agent', $this->agentName())
            ->firstOrFail();
    }
}
```
