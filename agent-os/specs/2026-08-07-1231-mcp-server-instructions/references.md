# References for MCP Server Instructions

## Similar Implementations

### Tool description management (primary template — DB/API/UI shape)

- **Location:**
  - `database/migrations/2026_01_01_000003_create_cortex_tool_description_tables.php`
  - `src/Models/ToolDescription.php`, `src/Models/ToolDescriptionVersion.php`
  - `src/Actions/CreateToolDescriptionVersionAction.php`, `PublishToolDescriptionVersionAction.php`, `ListToolDescriptionVersionsAction.php`, `ShowToolDescriptionAction.php`, `DeleteToolDescriptionAction.php`, `ListToolsAction.php`
  - `src/Http/Requests/ToolDescriptionRequest.php` (abstract binding base) + Show/Delete/IndexVersions/StoreVersion/PublishVersion concretes
  - `src/Http/Controllers/ToolDescriptionController.php`, `ToolController.php`
  - `src/Http/Resources/ToolDescriptionResource.php`, `ToolDescriptionVersionResource.php`, `ToolResource.php`
  - `routes/cortex.php` (tools block)
- **Relevance:** The exact feature being cloned for server instructions.
- **Key patterns:** Unique string natural key + no-FK publish pointer with circular-ref comment; immutable versions (`self::updating` throws); `lockForUpdate()->max('version') + 1` inside `DB::transaction`; one-liner controllers delegating to `$request->persist()`; rules delegated to `Action::rules()`; cache forget on publish/delete.

### Runtime override machinery (consumption side)

- **Location:** `src/Tools/ToolDescriptionOverrides.php` (scoped, memoized map), `src/Tools/Concerns/HasVersionedDescription.php` (trait: prefer override else parent), `src/Tools/Tool.php` (abstract base carrying trait), `src/Support/PublicationCache.php`, `src/Tools/ToolRegistry.php`
- **Relevance:** Template for `McpInstructionOverrides`, `HasVersionedInstructions`, `RefactorCircus\Cortex\Domains\McpServer\Support\Server`, `McpServerRegistry`.
- **Key patterns:** Scoped container binding (Octane-safe memoization); `PublicationCache` key-per-feature; registry lazy config load with string-key naming.

### Prompt version MCP tools (MCP layer template)

- **Location:** `src/Mcp/Request.php` (abstract base: `persist()`, `structuredCollection`), `src/Mcp/Requests/PromptMcpRequest.php` (memoized model binding), `src/Mcp/Tools/CreatePromptVersionTool.php`, `PublishPromptVersionTool.php`, `ListPromptVersionsTool.php`, `ShowPromptVersionTool.php`, `src/Mcp/Requests/DeletePromptMcpRequest.php` (delete response shape)
- **Relevance:** Tool descriptions are HTTP-only; prompt tools are the MCP pattern to mirror for the six server-instruction tools.
- **Key patterns:** Declarative tool shells (`#[Description]`, `handle(XMcpRequest $r) => $r->persist()`, `schema(JsonSchema)` with `->description()` per field); `ModelNotFoundException` → `Response::error('Not found.')`; `data` envelope.

### Vendor hook

- **Location:** `vendor/laravel/mcp/src/Server.php` `createContext()` (~lines 232-253), `vendor/laravel/mcp/src/Server/ServerContext.php` (`$instructions` promoted public non-readonly)
- **Relevance:** The single instructions resolution point; the trait mutates `$context->instructions` after `parent::createContext()`.

### Tests

- **Location:** `tests/Feature/Http/ToolDescriptionsTest.php` (9-case shape), `tests/Feature/ToolBaseTest.php` (override-served helper pattern), `tests/Feature/ToolRegistryTest.php`, `tests/Feature/PublicationCacheTest.php`, `tests/Feature/Mcp/*` (parity style), `tests/Fixtures/EchoTool.php`
- **Relevance:** Test suites to mirror for instructions/registry/server-base/parity coverage.
