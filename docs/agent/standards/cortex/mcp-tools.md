---
description: "Cortex MCP tools are declarative shells on Keystone's Mcp\\Tool that delegate to a {Verb}{Entity}McpRequest; every tool is listed in CortexServer::TOOLS; schema() and rules() describe the same fields, with rules from {Action}Data::rules()."
tags: [cortex, mcp, tools, requests]
kind: convention
---
# MCP Tools

Cortex's MCP surface mirrors its HTTP requests: the tool is a shell, the MCP request does the work. Complements [[data-objects]], [[mcp-model-binding]], [[mcp-schemas]] and [[mcp-responses]].

## Tool shells

- A tool declares only its `#[Description]`, `schema()`, `outputSchema()` and a one-line `handle()` that returns `$request->persist()`. No queries, validation or action calls in a tool.

```php
#[Description('Create a Cortex virtual agent. ...')]
final class CreateVirtualAgentTool extends Tool
{
    public function handle(CreateVirtualAgentMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array { ... }
}
```

## Base classes

- Cortex's own tools extend `RefactorCircus\Keystone\Mcp\Tool`, not Laravel's `Tool` directly: it carries the versioned description support through Cortex's overrides.
- Application tools extend `RefactorCircus\Cortex\Domains\Tool\Support\Tool` instead, since Keystone's base only serves suite packages.
- MCP requests extend `RefactorCircus\Cortex\Mcp\Request` (on Keystone's `Mcp\Requests\Request`), whose final `persist()` runs authorize, validate and not-found handling; never re-implement it in a tool or request.

## Requests

- `rules()` returns the action input's `{Action}Data::rules()` (plus identifier rules from the per-model base), never inline rules, as [[data-objects]] requires; `handle(array $validated)` builds the input with `fromValidated()` and calls the action.

## Registration

- List every tool in `CortexServer::TOOLS`; the server offers them behind ToolSearch.
- `ListCortexHistoryTool` is the shared history tool, a one-line subclass of Keystone's `ListHistoryTool`.

## Schema and rules in step

- `schema()` and `rules()` describe the same fields: the schema is what the calling model sees, the rules are what is enforced. Change both together or they drift.
