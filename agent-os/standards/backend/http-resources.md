# HTTP Resources

All payloads — HTTP and MCP — serialize through JSON resources: a model's resource sits beside the model in `src/Domains/{Domain}/Resources`, payloads with no model behind them in `src/Domains/{Domain}/Http/Resources` (or `src/Http/Resources` when shared, like `AgentRunResource`):

```php
/**
 * @mixin Prompt
 */
final class PromptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'published_version' => new PromptVersionResource($this->whenLoaded('publishedVersion')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- Slug is the public identity; omit internal ids unless there's a concrete need (not a hard ban, but default to leaving them out)
- Nested relations always via `whenLoaded()` — pairs with actions eager-loading what the resource needs
- Timestamps: `?->toIso8601String()`
- `@mixin ModelClass` docblock for static analysis
- `final`, no conditionals on request context
