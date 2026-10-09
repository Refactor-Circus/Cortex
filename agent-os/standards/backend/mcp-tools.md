# MCP Tools

Tools are declarative shells. All logic lives in `src/Domains/{Domain}/Mcp/Requests/*McpRequest.php`:

```php
#[Description('Create a Cortex prompt. ...')]
final class CreatePromptTool extends Tool
{
    public function handle(CreatePromptMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array { ... }
}
```

- Extend `RefactorCircus\Keystone\Mcp\Tool` (not Laravel's directly) — gives versioned description support through Cortex's overrides. Application tools extend `RefactorCircus\Cortex\Domains\Tool\Support\Tool` instead, since Keystone's base only serves suite packages
- List every tool in `CortexServer::TOOLS`; the server offers them behind ToolSearch. `ListCortexHistoryTool` is the shared history tool, a one-line subclass of Keystone's `ListHistoryTool`
- Request class: `rules()` returns `SomeAction::rules()` (plus slug lookup rules), `handle(array $validated)` resolves the action — deliberate mirror of the HTTP FormRequest `persist()` pattern: same mental model and test shape on both surfaces
- Base `Mcp\Request` extends `RefactorCircus\Keystone\Mcp\Requests\Request`, whose final `persist()` centralizes authorize → validate → not-found handling inside the `mcp` surface; never re-implement in a tool
- Never put queries or action calls in `Tool::handle()` — always delegate
- `schema()` and `rules()` describe the same fields — update both or they drift (schema is what the model sees; rules are what's enforced)
