---
description: "Cortex's runtime failures and their typed exceptions (VirtualAgentNotPublished, CircularAgentReference, AgentNotFound, ToolNotFound, McpServerNotFound), thrown by AgentFactory and the registries for callers and host apps to catch."
tags: [cortex, errors, exceptions, runtime]
kind: convention
---
# Runtime Exceptions

Complements [[exceptions]]: the runtime failures Cortex has and where they come from.

Every one extends the abstract `RefactorCircus\Cortex\Exceptions\CortexException` (on Keystone's `PackageException`), per [[exceptions]]. The not-found exceptions answer 404; the rest answer 409, except `UnexpectedResultException` (a Cortex bug), which answers 500. `tests/ArchTest.php` checks the base.

## Failures

| Condition | Exception | Thrown by |
|---|---|---|
| Virtual agent has no published version | `VirtualAgentNotPublishedException` | `AgentFactory` ([[instruction-resolution]]) |
| Sub-agent cycle found at runtime | `CircularAgentReferenceException` | `AgentFactory` ([[sub-agents]]) |
| Unregistered concrete agent name | `AgentNotFoundException` | `AgentRegistry` |
| Unregistered tool name | `ToolNotFoundException` | `ToolRegistry` |
| Unregistered MCP server name | `McpServerNotFoundException` | `McpServerRegistry` |

## Rules

- These are for system state found mid-execution, where there is no input field to key a 422 on. Input checks in the actions stay field-keyed `ValidationException` guards ([[domain-guards]]).
- Callers catch them by type: the run actions, and host apps that embed `AgentFactory` or the registries directly.
- A new runtime failure gets its own exception in its domain's `Exceptions/` and a row here.
