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

- Extend `JayI\Cortex\Domains\Tool\Support\Tool` (not Laravel's directly) — gives versioned description support
- Request class: `rules()` returns `SomeAction::rules()` (plus slug lookup rules), `handle(array $validated)` resolves the action — deliberate mirror of the HTTP FormRequest `persist()` pattern: same mental model and test shape on both surfaces
- Base `Mcp\Request::persist()` centralizes authorize → validate → not-found handling; never re-implement in a tool
- Never put queries or action calls in `Tool::handle()` — always delegate
- `schema()` and `rules()` describe the same fields — update both or they drift (schema is what the model sees; rules are what's enforced)
