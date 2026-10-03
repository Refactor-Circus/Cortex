# Versioned MCP Server Instructions

## Context

Cortex already versions tool descriptions (DB-backed overrides with publish pointers, served through HTTP API + UI, applied at runtime over code-declared `description()`). MCP server *instructions* have no equivalent — they're a static `#[Instructions]` attribute on `CortexServer`. This feature mirrors the tool-description shape for server instructions so operators can iterate/publish/rollback instructions without deploys. Shaped via /agent-os:shape-spec; user confirmed: server registry pattern, full HTTP↔MCP parity (unlike tool descriptions, which are HTTP-only), new "Servers" UI section, no visuals, mirror existing references.

**Runtime hook (verified in vendor):** `Laravel\Mcp\Server::createContext()` (`vendor/laravel/mcp/src/Server.php:232-253`) is the single instructions resolution point; `ServerContext::$instructions` is a promoted public non-readonly property. Trait overrides `createContext()`, calls parent, mutates `$context->instructions` when an override is published.

## Task 1: Save spec documentation

Create `agent-os/specs/2026-08-07-1231-mcp-server-instructions/`:
- `plan.md` — this full plan
- `shape.md` — scope + decisions (registry identity, full parity, Servers nav, vendor hook finding: no McpServer model existed; keyed by registry string name like tools)
- `standards.md` — full content of: `database/publish-pointer`, `database/immutable-versions`, `database/migrations`, `database/models`, `backend/actions`, `backend/action-transactions`, `backend/http-requests`, `backend/http-resources`, `backend/routes`, `backend/mcp-tools`, `backend/mcp-model-binding`, `backend/mcp-responses`, `backend/mcp-schemas`, `backend/publication-cache`, `backend/container-bindings`, `backend/config-docs`, `javascript/api-modules`, `javascript/form-errors`, `testing/test-layers`, `testing/mcp-http-parity`, `testing/testcase-environment`, `testing/arch-tests` (from `agent-os/standards/`)
- `references.md` — pointers: tool-description feature (migration `2026_01_01_000003`, `src/Models/ToolDescription*.php`, `src/Http/Controllers/ToolDescriptionController.php`, `src/Http/Requests/ToolDescriptionRequest.php` + concretes, `src/Actions/*ToolDescription*`, `src/Tools/ToolDescriptionOverrides.php`, `src/Tools/Concerns/HasVersionedDescription.php`, `resources/js/views/ToolDescription.vue`, `ToolsIndex.vue`, `api/tools.js`); prompt MCP tools (`src/Mcp/Tools/CreatePromptVersionTool.php`, `PublishPromptVersionTool.php`, `ListPromptVersionsTool.php`, `src/Mcp/Requests/PromptMcpRequest.php`); `src/Tools/ToolRegistry.php`; vendor hook `vendor/laravel/mcp/src/Server.php` `createContext()`
- No `visuals/` (none provided)

## Task 2: Database layer

- Migration `database/migrations/2026_01_01_000004_create_cortex_mcp_instruction_tables.php`:
  - `cortex_mcp_instructions`: ulid PK, `server` unique string, nullable indexed `published_version_id` (NO FK — copy circular-reference comment from `000003`), timestamps
  - `cortex_mcp_instruction_versions`: ulid PK, `foreignUlid('mcp_instruction_id')->constrained('cortex_mcp_instructions')->cascadeOnDelete()`, `unsignedInteger('version')`, `text('content')`, timestamps, `unique(['mcp_instruction_id','version'])`
- Models (final, HasUlids, HasFactory, explicit `$table`, `@property` docblocks — mirror `src/Models/ToolDescription.php` / `ToolDescriptionVersion.php`):
  - `src/Models/McpInstruction.php` — `$fillable=['server']`, `versions()`, `publishedVersion()`, `newFactory()`
  - `src/Models/McpInstructionVersion.php` — `$fillable=['version','content']`, `booted()` `self::updating` throws `LogicException('MCP server instruction versions are immutable. Create a new version instead.')`, `mcpInstruction()`
- Factories `database/factories/McpInstructionFactory.php` (`'server' => fake()->unique()->slug(2)`), `McpInstructionVersionFactory.php`
- Tests: additions to `tests/Feature/ModelsTest.php` (relations, factories, cascade)

## Task 3: Cache + overrides

- `src/Support/PublicationCache.php`: add `mcpInstructionsKey(): string` → `'cortex.published.mcp-instructions'`; update `config/cortex.php` cache banner comment mention
- `src/Mcp/McpInstructionOverrides.php` — verbatim mirror of `src/Tools/ToolDescriptionOverrides.php`: scoped, memoized map keyed by `server`, `for(string $server): ?string`
- Test: `tests/Feature/PublicationCacheTest.php` addition (invalidation on publish/delete)

## Task 4: Server registry

- `src/Mcp/McpServerRegistry.php` — mirror `src/Tools/ToolRegistry.php` (final, lazy config load):
  - `register(name, class)` — reject non-`Laravel\Mcp\Server` subclasses (`is_a(..., true)`)
  - `has()`, `names()`, `get()` (throws new `src/Exceptions/McpServerNotFoundException.php` mirroring `ToolNotFoundException`)
  - `nameFor(string $class): ?string` — reverse lookup for the trait; first registration wins (document)
  - `defaultInstructions(name)` — reflection ONLY, never instantiate (Server constructor needs Transport): walk `#[Instructions]` attribute up parent chain, else `instructions` property default value
  - `all()` — `{name, class, instructions}` where instructions = override ?? default (mirrors `ToolRegistry::all()`)
  - `loadConfigServers()` — built-in `'cortex' => CortexServer::class` first, then `config('cortex.mcp.servers', [])`; string keys name, unkeyed derive from reflected `#[Name]` (slug) else kebab class basename
- `config/cortex.php`: add `mcp.servers => []` with config-docs banner
- `src/Cortex.php`: add `servers(): McpServerRegistry` mirroring `tools()`
- `src/CortexServiceProvider.php` `register()`: `singleton(McpServerRegistry::class)`, `scoped(McpInstructionOverrides::class)`
- Fixture `tests/Fixtures/EchoServer.php` (extends new base, `#[Name]`/`#[Instructions]`)
- Tests: `tests/Feature/McpServerRegistryTest.php` — mirror `ToolRegistryTest` (runtime register, config load, built-in cortex always present, rejects invalid class, nameFor, name derivation, defaultInstructions from attribute + property)

## Task 5: Runtime override trait + base server

- `src/Mcp/Concerns/HasVersionedInstructions.php` — override `createContext()`: `$context = parent::createContext();` then `nameFor(static::class)` → `McpInstructionOverrides::for($name)` → mutate `$context->instructions` if non-null. Code comment noting dependency on `ServerContext::$instructions` staying public non-readonly.
- `src/Mcp/Server.php` — `abstract class Server extends \Laravel\Mcp\Server { use HasVersionedInstructions; }` (mirror `src/Tools/Tool.php` docblock style)
- `src/Mcp/CortexServer.php` — re-base onto `JayI\Cortex\Domains\McpServer\Support\Server`
- Tests: `tests/Feature/ServerBaseTest.php` — mirror `ToolBaseTest`: publish override for `'cortex'`, assert `createContext()->instructions` equals override; equals `#[Instructions]` text when unpublished/draft-only

## Task 6: Actions + resources

Actions (`src/Actions/`, final, `static rules()` + `execute()`), mirrors of the tool-description set:
- `CreateMcpInstructionVersionAction` (transaction, `firstOrCreate(['server'=>...])`, `lockForUpdate()->max('version')+1`, optional publish, cache forget)
- `PublishMcpInstructionVersionAction`, `ListMcpInstructionVersionsAction` (orderByDesc, unpaginated), `ShowMcpInstructionAction`, `DeleteMcpInstructionAction`, `ListMcpServersAction` (registry `all()`)

Resources (`src/Http/Resources/`):
- `McpServerResource` `{name, instructions}`
- `McpInstructionResource` `{server, published_version, published_content, created_at, updated_at}`
- `McpInstructionVersionResource` `{version, content, created_at}`

## Task 7: HTTP API

- Abstract `src/Http/Requests/McpInstructionRequest.php` (mirror `ToolDescriptionRequest`): `server()` validates against registry, abort 404; `instruction()` firstOr abort 404
- Concretes: `IndexMcpServersRequest`, `ShowMcpInstructionRequest`, `DeleteMcpInstructionRequest` (204), `IndexMcpInstructionVersionsRequest`, `StoreMcpInstructionVersionRequest` (rules from action, 201), `PublishMcpInstructionVersionRequest`
- Controllers: `McpServerController` (`index`), `McpInstructionController` (`show/destroy/versions/store/publish`) — one-liner `persist()` bodies
- `routes/cortex.php` (after tools block):

| Method | URI | Name |
|---|---|---|
| GET | `servers` | `cortex.servers.index` |
| GET | `servers/{server}/instructions` | `cortex.servers.instructions.show` |
| DELETE | `servers/{server}/instructions` | `cortex.servers.instructions.destroy` |
| GET | `servers/{server}/instructions/versions` | `cortex.servers.instructions.versions.index` |
| POST | `servers/{server}/instructions/versions` | `cortex.servers.instructions.versions.store` |
| POST | `servers/{server}/instructions/versions/{version}/publish` (whereNumber) | `cortex.servers.instructions.versions.publish` |

- Tests: `tests/Feature/Http/McpInstructionsTest.php` — mirror all 9 `ToolDescriptionsTest` cases (beforeEach registers `EchoServer`): create v1, 404 unregistered, increment+publish-on-store, publish older = rollback, list newest-first, cascade delete, immutability, override served via `cortex.servers.index`/registry, fallback when draft-only

## Task 8: MCP tools (full parity)

- Base `src/Mcp/Requests/ServerMcpRequest.php` — memoized `instruction()` via `firstOrFail()`; `server()` throws `(new ModelNotFoundException)->setModel(McpInstruction::class)` when registry misses → `Response::error('Not found.')` via base persist
- Concrete MCP requests (all rule `'server' => ['required','string']`, reuse Actions + Resources, `data` envelope per `mcp-responses` standard): `ListServersMcpRequest`, `ShowServerInstructionsMcpRequest`, `ListServerInstructionVersionsMcpRequest` (structuredCollection), `CreateServerInstructionVersionMcpRequest` (`...CreateMcpInstructionVersionAction::rules()`), `PublishServerInstructionVersionMcpRequest` (`version` required integer min:1), `DeleteServerInstructionsMcpRequest` (copy `DeletePromptMcpRequest` response shape exactly — check at implementation)
- Tool shells (`src/Mcp/Tools/`, extend `JayI\Cortex\Domains\Tool\Support\Tool`, `#[Description]`, `->description()` on every schema field): `ListServersTool`, `ShowServerInstructionsTool`, `ListServerInstructionVersionsTool`, `CreateServerInstructionVersionTool` (server, content, publish), `PublishServerInstructionVersionTool` (server, version), `DeleteServerInstructionsTool`
- `src/Mcp/CortexServer.php`: append `// MCP servers` group with six tools to `$tools`; extend `#[Instructions]` text to mention server-instruction management
- Tests: `tests/Feature/Mcp/ServerInstructionToolsTest.php` — parity per `testing/mcp-http-parity.md` via `CortexServer::tool(ToolClass::class, $args)`: mutation payloads equal HTTP `data`, list empty case, `ListServersTool` contains cortex entry, not-found errors

## Task 9: UI (Vue SPA)

- `resources/js/api/servers.js` — mirror `tools.js`: `list`, `instructions`, `destroyInstructions`, `instructionVersions`, `createInstructionVersion`, `publishInstructionVersion`
- `resources/js/views/ServersIndex.vue` — clone `ToolsIndex.vue` (Name / Instructions / link to editor)
- `resources/js/views/ServerInstructions.vue` — clone `ToolDescription.vue`: live panel with `override vN`/`from code` badges, versions table (view/publish), new-version form (content + publish checkbox, 422 → FieldErrors), remove-override ConfirmButton, 404 = no override yet
- `resources/js/router.js` — `/servers` (`servers.index`), `/servers/:server/instructions` (`servers.instructions`, props true)
- `resources/js/components/AppLayout.vue` — add Servers nav link after Tools

## Task 10: SDK + build + docs

- `npm run sdk:build` (Scramble export → `sdk/openapi.json` + `sdk/src/schema.d.ts`) — verify six new paths present
- `npm run build` (Vite → committed `public/app.js` + `public/app.css`)
- README: server-instructions section (endpoints, MCP tools, registry usage `Cortex::servers()->register()`, extending `JayI\Cortex\Domains\McpServer\Support\Server` / using trait)
- Run `package-generate-skill` flow (public API changed)

## Verification

- `composer test` (full: lint check, analyse, pest)
- Key behaviors: publish override → `CortexServer` `createContext()->instructions` returns override; delete → falls back to `#[Instructions]`; MCP↔HTTP parity payloads equal; UI flows against workbench (`composer serve`) — create/publish/rollback/remove via Servers page

## Risks

- Vendor coupling: mechanism depends on `ServerContext::$instructions` staying public non-readonly (true in installed laravel/mcp). Note in trait comment; recheck on upgrades.
- Registry never instantiates server classes (Transport ctor arg) — reflection only.
- `nameFor()` first-registration-wins if class registered twice — documented.
