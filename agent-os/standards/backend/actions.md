# Action Classes

All behavior flows through `src/Domains/{Domain}/Actions/*Action.php`. No exceptions — every HTTP endpoint and MCP tool delegates to an action.

```php
final class CreatePromptAction
{
    public static function rules(): array { ... } // even when empty

    public function execute(array $data): Prompt { ... }
}
```

- `final` class, static `rules()`, instance `execute()`
- `rules()` is the single source of validation. HTTP and MCP Request classes both return `SomeAction::rules()` — never inline rules. Two surfaces (API + MCP) must never drift.
- Controllers/tools stay thin: they call `$request->persist()` / `$request->handle()`, which resolves the action via `app()`
- Dependencies (e.g. `PublicationCache`) via constructor promotion
- New endpoint = new action first, then HTTP + MCP request wrappers
