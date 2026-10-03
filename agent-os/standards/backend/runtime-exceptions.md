# Runtime Exceptions

Runtime failures get dedicated exception classes in their domain's `Exceptions` directory (`src/Domains/{Domain}/Exceptions`) — unlike action-layer guards, which throw field-keyed `ValidationException`:

```php
final class PromptNotPublishedException extends RuntimeException
{
    public static function forPrompt(Prompt $prompt): self
    {
        return new self("Prompt [{$prompt->slug}] has no published version.");
    }
}
```

- Why: action guards reject user input (there's a field to key the 422 on). Runtime failures are system state discovered mid-execution — no input field exists; typed exceptions let callers (RunAgent action, host apps embedding AgentFactory) catch specific conditions
- Shape: `final`, extends `RuntimeException`, static `forX()` named constructor, slug (not id) in the message, message wraps identifiers in `[…]`
- Existing: `PromptNotPublishedException`, `CircularAgentReferenceException`, `ToolNotFoundException`
