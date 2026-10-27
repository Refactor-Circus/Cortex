# References for Prompts, Tools, Agents (API + MCP)

## Similar Implementations

### mono — FormRequest → Action → Resource pattern (HTTP + MCP symmetric)

- **Location:** `~/Herd/mono`
- **Relevance:** The user-designated reference for "APIs use Form Requests that resolve Actions reusable by MCP tools."
- **Key patterns borrowed:**
  - `app/Http/Request.php` — abstract FormRequest with `authorize()`, `rules()`, abstract `persist()`; controllers are one-liners (`return $request->persist();`). Ported as `src/Http/Request.php`.
  - `app/Mcp/Request.php` — abstract MCP request: final `persist()` = authorize → `Validator::validate($this->all(), $this->rules())` → `handle($validated)`; `ModelNotFoundException` → `Response::error('Not found.')`; `structuredCollection()` wraps lists in `['data' => ...]` because `Response::structured([])` throws. Ported as `src/Mcp/Request.php`.
  - MCP tools are thin: `#[Description]` + `schema(JsonSchema)` + `handle(XxxMcpRequest $request)` → `$request->persist()`.
  - Same JsonResource used by HTTP (`->response()`) and MCP (`->resolve()` into `Response::structured()`), guaranteeing payload parity.
  - Actions take primitives/models (never requests), return models/paginators; naming `{Op}{Model}Action`.
  - MCP server = class with `$tools` array; registered via `Mcp::web()` in `routes/ai.php`.
- **Deliberately dropped:**
  - Pennant feature-flag gating (`Action`/`GatedAction` bases, FQCN-derived feature classes) — not wanted in a package; plain final Actions instead.
  - HTTP↔MCP validation rule duplication — replaced by shared static `rules()` on Actions.
  - JSON:API resources (`JsonApiResource`) — plain `JsonResource` is package-appropriate.
  - `Domains/{Domain}/` layout — flatter `src/` layout at package scale.

### laravel/ai (v0.8.1 vendored in mono; target ^0.10)

- **Location:** `~/Herd/mono/vendor/laravel/ai`
- **Relevance:** Base SDK. `Agent` = interface (`instructions()`) + `Promptable` trait; `HasTools::tools()`; tools implement `description()/handle(Tools\Request)/schema(JsonSchema)`.
- **Load-bearing facts:**
  - `Promptable::getProvidersAndModels()` and `TextGenerationOptions::resolve()` prefer instance methods (`provider()`, `model()`, `temperature()`, `maxSteps()`, `maxTokens()`, `topP()`) over attributes → `DbAgent` is fully constructor-driven, no attributes.
  - `GeneratesText::resolveTool()` auto-wraps `Agent` instances found in `tools()` into `AgentTool` → sub-agents are just DbAgents in the tools iterable.
  - No agent/tool registry exists in laravel/ai — the package's `ToolRegistry` fills the gap.
  - Testing: `DbAgent::fake([...])` + `assertPrompted()`; unfaked prompting throws (no provider configured in Testbench).
  - Re-verify all of the above against v0.10 during implementation (0.x churn).

### laravel/mcp (v0.8.2 vendored; target ^0.9)

- **Location:** `~/Herd/mono/vendor/laravel/mcp`
- **Relevance:** MCP server layer. Server extends `Laravel\Mcp\Server` with `$tools` array + `#[Name]`/`#[Instructions]`; registered `Mcp::web(route, class)` / `Mcp::local(handle, class)` — callable from provider boot (config-gated in Cortex).
- **Key patterns:** test helper `Server::tool(ToolClass, [...])->assertOk()->assertStructuredContent(...)`; `mcp:start` / `mcp:inspector` artisan commands.
