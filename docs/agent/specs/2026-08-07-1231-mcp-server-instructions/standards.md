# Standards for MCP Server Instructions

The following standards apply to this work.

---

## database/publish-pointer

# Publish Pointer

Publishable entities point at their live version with a pointer column — deliberately without a foreign key:

```php
// No FK: circular reference with cortex_prompt_versions; integrity
// is enforced when publishing.
$table->ulid('published_version_id')->nullable()->index();
```

- The parent table and versions table reference each other — an FK would deadlock creation order, so the pointer stays FK-free with a comment saying why
- Integrity is enforced at the seam instead: the publish action resolves the version with `firstOrFail()` before assigning
- Publishing/unpublishing must invalidate `PublicationCache` for the entity
- Unpublished state = `null` pointer; resource serializes `published_version` only `whenLoaded`
- New publishable entity? Copy this shape: pointer column, migration comment, publish action, cache invalidation

---

## database/immutable-versions

# Immutable Versions

Version rows (`PromptVersion`, `ToolDescriptionVersion`) are never edited — new content is a new row with the next version number.

```php
protected static function booted(): void
{
    self::updating(function (): never {
        throw new LogicException('Prompt versions are immutable. Create a new version instead.');
    });
}
```

- Why model-level: agents can pin specific versions — silently editing published or pinned content would change agent behavior invisibly. The `updating()` hook makes the invariant unbreakable even by future code or tinker
- Schema backs it: `unique(['prompt_id', 'version'])`, version numbers assigned sequentially by the create action
- Any new versioned entity must follow the same shape: immutable rows + hook + unique compound index

---

## database/migrations

# Migration Conventions

Package migrations use a fixed date + sequence number, one file per feature's tables:

```
2026_01_01_000001_create_cortex_prompt_tables.php
2026_01_01_000002_create_cortex_agent_tables.php
2026_01_01_000003_create_cortex_tool_description_tables.php
```

- Fixed `2026_01_01` date + incrementing sequence — package migrations must run in a stable, reviewable order in any host app regardless of authoring time; next migration takes `000004`
- One migration creates all tables for a feature (parent + versions + pivots)
- `ulid('id')->primary()`; FKs via `foreignUlid(...)->constrained(...)->cascadeOnDelete()`
- Compound uniques where the domain demands (`unique(['prompt_id', 'version'])`)
- `down()` drops tables in reverse dependency order
- Anonymous class migrations, `declare(strict_types=1)`

---

## database/models

# Model Conventions

```php
/**
 * @property string $id
 * @property string $slug
 * @property Carbon|null $created_at
 */
final class Prompt extends Model
{
    /** @use HasFactory<PromptFactory> */
    use HasFactory;
    use HasUlids;

    protected $table = 'cortex_prompts';

    protected $fillable = ['name', 'slug', 'description'];

    /** @return HasMany<PromptVersion, $this> */
    public function versions(): HasMany { ... }

    protected static function newFactory(): PromptFactory { ... }
}
```

- `final`; explicit `$table` with `cortex_` prefix (package tables live in host apps)
- `HasUlids` on every model — ULIDs avoid id collisions on export/import between environments and don't leak row counts; pairs with slug as public identity
- `@property` docblock for every column; relations carry generics (`HasMany<PromptVersion, $this>`)
- Explicit `$fillable` (no `$guarded = []`), explicit FK names in relations
- `newFactory()` points at the package factory namespace

---

## backend/actions

# Action Classes

All behavior flows through `src/Actions/*Action.php`. No exceptions — every HTTP endpoint and MCP tool delegates to an action.

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

---

## backend/action-transactions

# Action Transactions & Return Shape

Every mutating `execute()` wraps its writes in `DB::transaction`:

```php
public function execute(array $data): Prompt
{
    return DB::transaction(function () use ($data): Prompt {
        // all writes here
    });
}
```

- Any write — even a single one — goes inside the transaction. Some older single-write actions skip this; bring them in line when touched, don't copy them.

Mutations return the model with relations explicitly loaded:

```php
return $prompt->load('publishedVersion');
return $agent->refresh()->load(['prompt', 'pinnedVersion', 'subAgents']);
```

- HTTP and MCP serialize the result through the same Resource — relations must be eager-loaded so both surfaces return complete, identical payloads with no lazy-load surprises.
- After updates, `refresh()` first, then `load()`.

---

## backend/http-requests

# HTTP persist() Requests

Controllers are routing glue — every method is one line:

```php
public function store(StorePromptRequest $request): JsonResponse
{
    return $request->persist();
}
```

The FormRequest is the complete HTTP use case — validation, authorization, action call, response:

```php
final class StorePromptRequest extends Request
{
    public function rules(): array
    {
        return CreatePromptAction::rules();
    }

    public function persist(): JsonResponse
    {
        $prompt = app(CreatePromptAction::class)->execute($this->validated());

        return (new PromptResource($prompt))->response()->setStatusCode(201);
    }
}
```

- Extend `RefactorCircus\Cortex\Http\Request` (abstract persist() enforces the shape)
- One request class per operation; controllers stay identical across models and mirror the MCP request layer 1:1
- `rules()` always delegates to the action's static rules — never inline
- Status codes: 201 create, 200 default, 204 (Response) for deletes
- Never put action calls or response building in controllers

---

## backend/http-resources

# HTTP Resources

All payloads — HTTP and MCP — serialize through `src/Http/Resources`:

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

---

## backend/routes

# Route Conventions

All API routes live in `routes/cortex.php` inside one group:

```php
Route::prefix($prefix)->middleware($middleware)->name('cortex.')->group(...);
```

- Prefix and middleware come from `cortex.routes.*` config — never hardcode
- Every route is named under `cortex.` (`cortex.prompts.versions.publish`)
- Model binding by slug: `{prompt:slug}`, `{agent:slug}` — never by id
- Version segments constrained: `->whereNumber('version')`
- Domain operations (publish, run) are `POST` with a verb suffix on the resource path — never overload PATCH with mode flags:

```php
Route::post('prompts/{prompt:slug}/versions/{version}/publish', ...);
Route::post('agents/{agent:slug}/run', ...);
```

- Explicit route definitions per action — no `Route::resource()`

---

## backend/mcp-tools

# MCP Tools

Tools are declarative shells. All logic lives in `src/Mcp/Requests/*McpRequest.php`:

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

- Extend `RefactorCircus\Cortex\Domains\Tool\Support\Tool` (not Laravel's directly) — gives versioned description support
- Request class: `rules()` returns `SomeAction::rules()` (plus slug lookup rules), `handle(array $validated)` resolves the action — deliberate mirror of the HTTP FormRequest `persist()` pattern: same mental model and test shape on both surfaces
- Base `Mcp\Request::persist()` centralizes authorize → validate → not-found handling; never re-implement in a tool
- Never put queries or action calls in `Tool::handle()` — always delegate
- `schema()` and `rules()` describe the same fields — update both or they drift (schema is what the model sees; rules are what's enforced)

---

## backend/mcp-model-binding

# MCP Model Binding

Slug→model resolution lives in an abstract per-model request base — the MCP analog of route-model binding:

```php
abstract class PromptMcpRequest extends Request
{
    private ?Prompt $prompt = null;

    protected function prompt(): Prompt
    {
        return $this->prompt ??= Prompt::query()
            ->where('slug', $this->get('slug'))
            ->firstOrFail();
    }
}
```

- Always create the per-model base for any slug→model lookup, even for a single tool — consistency over YAGNI
- Memoize with `??=` — rules and handle may both need the model
- Use `firstOrFail()`; base `persist()` catches ModelNotFoundException and returns `Response::error('Not found.')` — no manual existence checks
- Concrete requests add `'slug' => ['required', 'string']` to rules alongside the action's rules

---

## backend/mcp-responses

# MCP Response Shape

All structured MCP responses wrap payloads in a `data` envelope:

```php
// lists
return $this->structuredCollection(PromptResource::collection($prompts)->resolve());
// → { "data": [ ... ] }

// single items — same envelope
return Response::structured(['data' => (new PromptResource($prompt))->resolve()]);
```

- Serialize through the same `Http/Resources` classes as the HTTP API — never hand-build arrays
- Envelope is mandatory for lists: `Response::structured([])` throws, so an empty list must ship as `{ "data": [] }` (that's why `structuredCollection()` exists)
- Some older single-item responses return the bare resource with no envelope — legacy; wrap in `data` when touched, don't copy
- Errors: `Response::error('...')` — base request maps ModelNotFoundException → `Not found.`

---

## backend/mcp-schemas

# MCP Schema Conventions

Every schema field gets a `->description()` — it's the model-facing documentation:

```php
'slug' => $schema->string()->description('Unique identifier (letters, numbers, dashes, underscores).')->required(),
'prompt_version' => $schema->integer()->description('Pin a specific prompt version. Omit to follow the published version.')->min(1),
```

- Descriptions state defaults and behavior ("Defaults to true.", "Replaces the whole list.") — the model can't read the code
- Any schema fragment used by 2+ tools goes into a `Mcp/Tools/Concerns` trait (e.g. `DescribesAgentPayload` for create/update agent fields) so tools never drift
- Tool descriptions: `#[Description('...')]` attribute is the code-declared fallback; a published Cortex tool-description version overrides it at runtime (`HasVersionedDescription`) — keep the attribute accurate anyway, it's what ships when nothing is published

---

## backend/publication-cache

# PublicationCache

`Support\PublicationCache` caches only published content — data that changes solely when someone publishes:

- Redis available → `flexible()` stale-while-revalidate with `cortex.cache.fresh` / `cortex.cache.stale` windows
- Otherwise → `rememberForever()` until explicit invalidation
- Pin the store with `cortex.cache.store` (tests pin `array` — the Redis probe would otherwise find a local Redis and leak state)

Obligations:

- Every action that changes publication state — publish, unpublish, delete — must `forget()` the entity's key; the cache never expires published content on its own with the non-Redis store
- Keys come from the cache's own helpers (`promptKey()`, …), never hand-built strings
- Don't reach for it for anything that changes outside the publish flow — that's what makes rememberForever safe

---

## backend/container-bindings

# Container Bindings

Binding lifetime in `CortexServiceProvider::register()` is a deliberate choice per service:

```php
$this->app->singleton(Tools\ToolRegistry::class);        // static code registrations
$this->app->scoped(Tools\ToolDescriptionOverrides::class); // memoizes DB state
$this->app->singleton(Cortex::class);
```

- `singleton` for services holding code-level state (tool registrations) — safe for the process lifetime
- `scoped` for services that memoize database state: `ToolDescriptionOverrides` caches published descriptions, and a singleton would serve stale data across requests in long-running workers (Octane, queues)
- Rule when adding a binding: memoizes DB/request state → `scoped`; pure code/config state → `singleton`
- Actions are never bound — resolved fresh via `app(Action::class)` each call

---

## backend/config-docs

# Config Documentation

`config/cortex.php` is user-facing documentation. Every section gets a banner comment:

```php
/*
|--------------------------------------------------------------------------
| HTTP API Routes
|--------------------------------------------------------------------------
|
| The prefix and middleware applied to the Cortex management API routes.
| Add authentication middleware (e.g. auth:sanctum) before exposing
| these routes in production - they manage and execute agents.
|
*/
```

Banner must state:
- What the section controls
- Security implications — anything exposing routes names the auth middleware to add before production (mandatory)
- Available modes/values and what each does (e.g. the four `ui.auth.mode` values)

New config key without a documented section = incomplete change.

---

## javascript/api-modules

# API Modules

Views never call `fetch` or the SDK directly. Each resource gets a module in `resources/js/api/`:

```js
import { sdk, unwrap } from './client';

export default {
    list: (page = 1) => unwrap(sdk.GET('/cortex/prompts', { params: { query: { page } } })),
    show: (slug) => unwrap(sdk.GET('/cortex/prompts/{prompt}', { params: { path: { prompt: slug } } })),
};
```

- `unwrap()` converts openapi-fetch results to payload-or-throw: returns `data`, throws `ApiError(status, message, errors)` on failure — every view handles errors the same way
- `client.js` owns auth headers, the single 401 retry, and error normalization — bypassing it silently loses all three
- SDK is the generated `@refactor-circus/cortex-sdk` OpenAPI client; spec churn stays inside `api/`, views only see named methods
- Path params are slugs (matching backend slug binding), never ids

---

## javascript/form-errors

# Form & Error Handling

Form views keep two error channels plus busy flags:

```js
const form = ref({ ... });
const errors = ref({});      // field errors, keyed by input name
const error = ref(null);     // banner message
const loading = ref(editing); // initial fetch
const saving = ref(false);    // submit in flight

try {
    await prompts.create({ ... });
} catch (e) {
    if (e instanceof ApiError && e.status === 422) {
        errors.value = e.errors;   // → <FieldErrors :errors="errors.name" />
    } else {
        error.value = e.message;   // → <Alert>
    }
} finally {
    saving.value = false;
}
```

- 422 → `errors` feeds `FieldErrors` under each input; anything else → `error` banner via `Alert` — mirrors backend field-keyed ValidationException standard
- Reset both channels at submit start; clear busy flag in `finally`
- Optional text fields submit `value || null`, not empty strings
- Use shared components (`Alert`, `FieldErrors`, `Spinner`, `ConfirmButton`, `Pagination`) — don't reinvent per view

---

## testing/test-layers

# Test Layers

Behavior is tested once, at the action; surfaces test only their own wiring.

| Layer | Location | Invokes via | Covers |
|---|---|---|---|
| Actions | `tests/Feature/*ActionsTest.php` | `app(Action::class)->execute()` | Business rules, edge cases, guards |
| HTTP | `tests/Feature/Http/` | `$this->getJson(route('cortex.…'))` | Status codes, validation mapping, JSON paths |
| MCP | `tests/Feature/Mcp/` | `CortexServer::tool(Tool::class, $args)` | Envelopes, errors, HTTP parity |

- Don't duplicate business cases per surface — a delete-in-use rule is one ActionsTest case, not three
- HTTP tests always use `route('cortex.…')` names, never literal URLs
- Setup via model factories; fixtures (fake tools, token resolvers) live in `tests/Fixtures`

---

## testing/mcp-http-parity

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
- Invoke tools via `CortexServer::tool(ToolClass::class, $args)` — never construct requests by hand

---

## testing/testcase-environment

# TestCase Environment

All tests extend `Tests\TestCase` (Orchestra Testbench), bound in `Pest.php` via `uses(TestCase::class)->in(__DIR__)`.

Pinned environment — don't undo these:

- `cortex.cache.store => array`: the PublicationCache availability probe would otherwise find a running local Redis and leak published state across tests
- `database.default => testing` with `foreign_key_constraints => true` — FK violations must fail in tests
- Providers: `AiServiceProvider`, `McpServiceProvider`, then `CortexServiceProvider`
- Package migrations loaded from `database/migrations`

Per-test config: call `config()->set(...)` at the start of the test (or a dedicated TestCase subclass like the Ui variants) — never mutate the shared `TestCase` for one feature.

---

## testing/arch-tests

# Arch Tests

Global code constraints are executable — they live in `tests/ArchTest.php`, not in review checklists:

```php
arch()->preset()->php();
arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('RefactorCircus\Cortex')
    ->toUseStrictTypes();
```

- Every PHP file: `declare(strict_types=1)` — enforced
- Banned everywhere: `dd`, `ddd`, `env` (use `config()`), `exit`
- New global constraint? Add an arch expectation here — that's the enforcement point
