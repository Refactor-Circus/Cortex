---
description: "Sub-agents are built recursively as tools on the parent agent, with dual cycle guards: assertNoCycles at write time (field-keyed 422) and a by-value visited list at runtime (CircularAgentReferenceException)."
tags: [cortex, agents, sub-agents, cycles]
kind: architectural
---
# Sub-Agents

Sub-agents are built recursively and attached as tools on the parent agent, and cycles are guarded twice on purpose.

## Two guards

- Write time: `assertNoCycles()` in the agent actions gives a friendly field-keyed 422; see [[domain-guards]].
- Runtime: a `$visited` list in `AgentFactory` throws `CircularAgentReferenceException`, protecting against data that bypassed the actions (imports, direct DB writes, races), so execution can never loop forever.
- Keep both when touching sub-agent wiring; neither replaces the other.

## Visited list

- `$visited` is passed by value down each branch, so diamond shapes (two parents sharing a sub-agent) are legal; only true cycles throw.

```php
if (in_array($key, $visited, true)) {
    throw CircularAgentReferenceException::forAgent($agent);
}

$visited[] = $key;
```
