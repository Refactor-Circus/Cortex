# Cortex: Prompts, Tools, Agents — API + MCP (laravel/ai base)

## Context

`refactor-circus/cortex` is a pristine Laravel package skeleton ("AI Orchestration") with zero domain code. Goal: API-first + MCP-first package to manage **prompts (immutable versioning + published pointer), tools, and agents/subagents**, built on `laravel/ai` (^0.10) and `laravel/mcp` (^0.9). API and MCP must have full tool parity.

Pattern source: `~/Herd/mono` — Controller → FormRequest `persist()` → `app(Action::class)->execute()` → Resource; MCP mirrors with `Laravel\Mcp\Server\Tool` → custom `Mcp\Request` `persist()` → **same Action** → `Response::structured()` of the same Resource. The Action is the shared unit.

### Shaping decisions (confirmed with user)

1. **DB agents + code tools** — tools are PHP classes registered in a `ToolRegistry` (config + runtime API); agents are DB records referencing prompt (pinned version or latest published), tool names, sub-agents, provider/model/settings. Runtime factory builds a laravel/ai agent from the record.
2. **Management + execution** — CRUD plus run-agent endpoint/MCP tool.
3. **Immutable prompt versions + published pointer** — editing content creates a new version; publish moves the pointer.
4. **Shared validation rules per operation** — static `rules()` on each Action, consumed by both HTTP FormRequest and MCP request.
5. **Fail-closed defaults** — MCP web route disabled by default; API routes ship with `['api']` middleware only and README documents auth hardening.
6. No visuals. `agent-os/product/` absent; `standards/index.yml` empty stub — no standards to inject.

### Key laravel/ai facts (verified against vendored v0.8; re-verify on 0.10 in Task 2)

- `Agent` = interface (`instructions()`) + `Promptable` trait; `HasTools::tools(): iterable`.
- `Promptable::getProvidersAndModels()` / `TextGenerationOptions::resolve()` prefer **instance methods** `provider()`, `model()`, `temperature()`, `maxSteps()`, `maxTokens()`, `topP()` over attributes → `DbAgent` needs no attributes.
- Sub-agents: any `Agent` instance in `tools()` is auto-wrapped in `AgentTool` (`GeneratesText::resolveTool`).
- No agent/tool registry exists in laravel/ai — we build it.
- `laravel/mcp` is the separate MCP-server package; server = class with `$tools` array, registered `Mcp::web()`/`Mcp::local()`. Test helper: `Server::tool(ToolClass, [...])->assertOk()`.
- `Response::structured([])` throws — lists always need a `['data' => ...]` envelope.
- Testing: `DbAgent::fake([...])` + `assertPrompted()`; tests must always fake (no provider configured otherwise).

## Architecture

One vertical per operation. HTTP controllers and MCP tools are one-liners (`return $request->persist();`); both resolve the same plain `final` Action. Namespace `RefactorCircus\Cortex\`:

```
src/
  Cortex.php                     # manager: tools(), agent($slug), run($slug, $input)
  CortexServiceProvider.php
  Actions/                       # one final class per op: static rules() + execute()
  Models/                        # Prompt, PromptVersion, Agent
  Tools/ToolRegistry.php
  Runtime/DbAgent.php            # Agent + HasTools + Promptable, ctor-driven
  Runtime/AgentFactory.php       # DB record -> DbAgent (recursive, cycle guard)
  Http/Request.php               # abstract FormRequest w/ persist() (port from mono)
  Http/Controllers/  Http/Requests/  Http/Resources/
  Mcp/Request.php                # abstract MCP request w/ persist() (port from mono)
  Mcp/CortexServer.php
  Mcp/Tools/  Mcp/Requests/
  Exceptions/                    # ToolNotFound, PromptNotPublished, CircularAgentReference
database/factories/              # autoload-dev, RefactorCircus\Cortex\Database\Factories
```

## Database

Delete placeholder migration. Two new migrations, `cortex_` prefix:

- `cortex_prompts`: id, name, slug unique, description nullable, `published_version_id` nullable index (**no FK** — circular ref + SQLite; integrity in `PublishPromptVersionAction`), timestamps.
- `cortex_prompt_versions`: id, prompt_id FK cascade, version unsignedInteger, content longText, timestamps, unique(prompt_id, version). Immutability = model boot `updating` guard (throw) + no update Action.
- `cortex_agents`: id, name, slug unique, description, prompt_id FK nullable restrictOnDelete, prompt_version_id FK nullable restrictOnDelete (null = follow published), provider, model, settings json (temperature/max_steps/max_tokens/top_p), `tools` json default `[]` (**JSON column, not pivot** — names reference in-code registry; validate `Rule::in($registry->names())`), timestamps.
- `cortex_agent_agent`: agent_id + sub_agent_id FKs cascade, unique pair.

Models with relations (`Prompt::versions/publishedVersion/agents`, `Agent::prompt/pinnedVersion/subAgents/parentAgents` self-belongsToMany), casts, `HasFactory` + factories (test-only via autoload-dev). Route binding by `{prompt:slug}` / `{agent:slug}` in routes, not `getRouteKeyName`.

## Registries

`ToolRegistry` singleton: `register(name, class)`, `get(name)` (container-resolved, throws `ToolNotFoundException`), `has()`, `names()`, `all()` (name/class/description/serialized schema via injected JsonSchema builder). Seeds lazily from `config('cortex.tools')`. v1 accepts `Laravel\Ai\Contracts\Tool` classes only (laravel/mcp tools bridge via `McpServerTool` wrapper — document, don't build dual-contract registry).

No AgentRegistry — agents are DB-only. `Cortex` manager: `tools(): ToolRegistry`, `agent(string $slug): Agent`, `run(string $slug, string $input)`. Update facade docblock.

## Actions (16 ops — shared rules source)

Each: `public static function rules(...): array` (payload only; identity via route binding on HTTP, slug rule prepended on MCP) + `execute(...)` taking primitives/models.

Prompts: `ListPrompts`, `CreatePrompt` (creates prompt + version 1 in transaction, `publish` bool default true), `ShowPrompt`, `UpdatePrompt` (metadata only), `DeletePrompt` (refuse when agents attached), `ListPromptVersions`, `CreatePromptVersion` (`content`, `publish` default false, version = max+1 in transaction), `ShowPromptVersion` (by prompt + version number), `PublishPromptVersion` (asserts ownership, moves pointer).

Agents: `ListAgents`, `CreateAgent`, `ShowAgent`, `UpdateAgent`, `DeleteAgent`, `ListTools`, `RunAgent`.

**Attach/detach folds into Create/Update with whole-list sync semantics** for `tools`, `sub_agents` (slugs), `prompt` + `prompt_version` — no standalone attach/detach ops (16 ops instead of 22+). Cycle prevention at write time: Create/UpdateAgent walk proposed sub-agent graph, `ValidationException` on any path back to self.

## HTTP API

`routes/cortex.php`: `Route::prefix(config('cortex.routes.prefix'))->middleware(config('cortex.routes.middleware'))->name('cortex.')`.

Routes (default prefix `cortex`): REST `prompts` CRUD (`{prompt:slug}`), nested `prompts/{prompt:slug}/versions` (index/store/show `{version}` int/`{version}/publish` POST), REST `agents` CRUD, GET `tools`, POST `agents/{agent:slug}/run`.

Controllers one-line; ~16 FormRequests delegating `rules()` to Actions, `persist()` → Action → Resource. Resources: `PromptResource` (embeds published version number + content), `PromptVersionResource`, `AgentResource` (tools, sub_agent slugs, prompt slug + pin), `ToolResource`, `AgentRunResource` (text, usage if available).

## MCP

`Mcp/Request.php` port: final `persist()` = authorize → `Validator::validate($this->all(), $this->rules())` → `handle($validated)`; `ModelNotFoundException` → `Response::error('Not found.')`; `structuredCollection()` data-envelope helper.

16 tools in `Mcp/Tools/` (`ListPromptsTool` … `RunAgentTool`), each `#[Description]` + `schema(JsonSchema)` + `handle(XxxMcpRequest $request): return $request->persist();`. MCP requests reuse `Action::rules()` + slug identity rule, return `Response::structured((new XxxResource($m))->resolve())` — same resources as HTTP = payload parity.

`CortexServer` extends `Laravel\Mcp\Server`, `$tools` = 16 classes, name/instructions attributes.

Registration: config-gated in provider `boot()` — `Mcp::web(route, CortexServer::class)->middleware(...)` when enabled, `Mcp::local(handle, ...)` when enabled. **Both disabled by default (fail-closed).** README documents enabling + auth middleware, and manual `routes/ai.php` alternative.

## Execution

`DbAgent`: ctor `(string $instructions, iterable $tools, ?string $provider, ?string $model, array $settings)`; implements `instructions()`, `tools()`, plus `provider()/model()/temperature()/maxSteps()/maxTokens()/topP()` returning record values or null (falls back to `ai.default` config).

`AgentFactory::make(Models\Agent $record, array $visited = [])`:
1. Instructions: pinned version content → else published version content → attached-but-unpublished throws `PromptNotPublishedException` → no prompt = empty string.
2. Tools from registry by name.
3. Sub-agents recurse with visited-set; revisit throws `CircularAgentReferenceException` (defense-in-depth). Sub-agent DbAgents appended into tools iterable (auto-wrapped as AgentTool).

`RunAgentAction::execute(Agent $record, string $input)` → `$factory->make($record)->prompt($input)`. Streaming/queue/conversations out of scope v1.

## Config (`config/cortex.php`)

```php
return [
    'routes' => ['prefix' => 'cortex', 'middleware' => ['api']],
    'mcp' => [
        'web' => ['enabled' => false, 'route' => 'mcp/cortex', 'middleware' => []],
        'local' => ['enabled' => false, 'handle' => 'cortex'],
    ],
    'tools' => [],
];
```

No `defaults.provider/model` — laravel/ai's `ai.default` already covers it.

Provider: `register()` keeps config merge + `Cortex` singleton (constructed w/ registry+factory), adds `ToolRegistry` singleton. `boot()` keeps loadRoutesFrom + publishes; adds config-gated MCP registration. `publishesMigrations` publish-only; tests load migrations directly.

## Tasks

### Task 1: Save spec documentation
Create `agent-os/specs/2026-07-26-1344-prompts-tools-agents-api-mcp/`:
- `plan.md` — this plan
- `shape.md` — scope, the 5 decisions above, context (no visuals; references = mono + vendored laravel/ai; product docs N/A)
- `standards.md` — note index.yml is empty; package conventions from CLAUDE.md apply
- `references.md` — mono pattern summary (Http/Request.php, Mcp/Request.php, GatedAction seam, Domains layout, JSON:API resources; what we borrow vs drop — drop Pennant gating, drop rule duplication, drop JSON:API in favor of plain JsonResource)

### Task 2: Deps + config + cleanup
composer.json: add `laravel/ai ^0.10`, `laravel/mcp ^0.9`; bump illuminate constraint to `^12.62||^13.15` (json-schema transitive floor). Rewrite `config/cortex.php`. Delete placeholder migration/config key; update the 6 existing ExampleTest assertions touching placeholders. Run `composer test`; **re-verify 0.10/0.9 API drift** (method-over-attribute resolution, AgentTool wrapping, PendingTestResponse helpers) before proceeding.

### Task 3: Schema + models
2 migrations, 3 models, factories, unit tests (immutability guard, relations, casts, version sequencing).

### Task 4: ToolRegistry + Cortex manager
`Tools/ToolRegistry.php`, rework `src/Cortex.php` + facade docblock, `ListToolsAction`, `tests/Fixtures/EchoTool.php`, unit tests.

### Task 5: Prompt actions
9 prompt/version actions with static `rules()`, action-level tests.

### Task 6: Agent actions + runtime
5 agent actions (cycle rejection), `Runtime/DbAgent.php`, `Runtime/AgentFactory.php`, `RunAgentAction`, 3 exceptions, tests with `DbAgent::fake()` + `assertPrompted()`.

### Task 7: HTTP surface
`Http/Request.php`, 5 controllers, ~16 FormRequests, 5 resources, `routes/cortex.php`, feature tests per op (happy path + validation + 404, by route name).

### Task 8: MCP surface
`Mcp/Request.php`, 16 tools + 16 MCP requests, `CortexServer`, provider MCP registration (fail-closed), parity tests: `CortexServer::tool(X, [...])->assertOk()` + assert structured payload matches HTTP resource shape.

### Task 9: Docs + polish
README (install, config, publish tags, security/auth guidance, MCP connect instructions), CHANGELOG, regenerate Boost skill via `package-generate-skill` skill.

Use `package-scaffold` + `package-testing` skills during Tasks 2–8; `package-compatibility` for Task 2 constraint check.

## Verification

- `composer test` (phpstan level 7 → pint → 100% type coverage → Pest parallel) green after each task.
- HTTP: feature tests hit every named route.
- MCP: `CortexServer::tool(...)` tests for all 16 tools; parity assertion = structured content matches HTTP resource `->resolve()` shape.
- Execution e2e (faked): create prompt → publish → create agent w/ tool + sub-agent → run via HTTP POST and via `RunAgentTool` → `DbAgent::assertPrompted()`.
- Manual: `composer build && composer serve`, hit API; `php artisan mcp:start cortex` + `mcp:inspector` with local server enabled in workbench.

## Risks

- **0.x churn** (laravel/ai 0.10 / laravel/mcp 0.9): design verified against vendored 0.8.x; Task 2 re-verifies. Caret pins minor; widen deliberately per release.
- **json-schema transitive floor** raises effective Laravel minimum — CI lowest-deps matrix catches.
- **`Response::structured([])` throws** — data envelope mandatory in MCP base request.
- **Circular prompts↔versions FK** — pointer column unconstrained by design; integrity in publish action.
