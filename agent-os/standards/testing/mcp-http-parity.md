# MCP/HTTP Parity Tests

Every MCP tool that returns a resource gets a parity test: its structured content must equal the HTTP payload for the same record.

```php
it('creates a prompt with parity to the http payload', function () {
    $mcp = CortexServer::tool(CreatePromptTool::class, [
        'name' => 'Support', 'slug' => 'support', 'content' => 'You are helpful.',
    ])->assertOk();

    $http = $this->getJson(route('cortex.prompts.show', 'support'))->json('data');

    $mcp->assertStructuredContent($http);
});
```

- HTTP is the canonical shape; MCP must match — this is the executable check on the shared-Resource contract
- Required for every mutation tool; list tools additionally test the empty case (`data: []` must not error)
- `list-cortex-history-tool` is exempt: it returns audit entries, not a resource, and is tested against the history route's not-installed answer instead
- Invoke tools via `CortexServer::tool(ToolClass::class, $args)` — never construct requests by hand
