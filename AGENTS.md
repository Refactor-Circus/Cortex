# Cortex

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `refactor-circus/cortex`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Layout

The package follows the mono domain-module layout, described in the global standard `architecture/domain-modules` and in `docs/agent/standards/cortex/domains.md`. Code lives in `src/Domains/{VirtualAgent,ConcreteAgent,Tool,ConcreteServer,RedirectDomain}` (namespace `RefactorCircus\Cortex\Domains\{Domain}`), each with its own `{Domain}ServiceProvider` registered by `Domains\DomainServiceProvider`. Models are named `{Entity}Model`. Package-wide pieces (`Cortex`, the facade, the base requests, `Mcp\CortexServer`, `Support\`) stay at the top level. The package-wide reads a UI needs (agent search, runnable agents, settings summary) live in `VirtualAgent` beside `providers.index`. `config/cortex.php` stays one file.

Cortex v2's PHP is a JSON API and an MCP server only: no views, screens, Blade directives or Atrium code in `src/`, `config/`, routes or the workbench, and `tests/Feature/NoAtriumTest.php` keeps it that way. Atrium v2 is the optional UI, and Cortex ships its own Atrium front-end module for it: JS only, sources in `atrium/` (Vue, built with the `@refactor-circus/atrium/vite` preset, typed by `client/`), built to `dist/atrium/` (committed) and declared in `composer.json` `extra.atrium`, so Atrium's page loads its script. What the integration adds to Atrium (feature, navigation, settings panel, search source, screens and the redirect-domains hook with its `cortex.mcp` tab and `cortex.redirect-domains` section) is declared in the static JSON manifest `atrium/src/integration.json` (Atrium's MODULES.md §8); the build validates it and writes `dist/atrium/integration.json`, named by `extra.atrium.integration`. Every screen key it declares must be in `src/atrium.ts`'s `screens` map and every hook tab/section key in the module's `extensions`; `atrium/test/integration.test.ts` checks both. Change it there and rebuild. The module's components compose Nuxt UI (`UTable`, `UForm`, `UInput`, `USelect`, `UCheckbox`, `UEmpty`, `ULink`, ...) and the SDK's `Atrium*` components, never raw `<button>`, `<input>`, `<select>`, `<textarea>`, `<table>`, `<details>` or `<a href>`; `atrium/test/nuxt-ui-guard.test.ts` enforces it (Atrium D-091, D-019). Rebuild after changing `atrium/`: `pnpm -C atrium install`, `pnpm -C atrium typecheck`, `pnpm -C atrium test`, `pnpm -C atrium build`, then commit `dist/atrium/`; `tests/Feature/AtriumModuleTest.php` fails while the build is older than its sources, and the `ui` workflow rebuilds and diffs.

## Keystone

Cortex stands on `refactor-circus/keystone`, the shared runtime of the Refactor Circus suite; read its README before changing base classes.

- `CortexServiceProvider` extends `RefactorCircus\Keystone\Support\PackageServiceProvider`. `definition()` describes the package (`cortex`, `RefactorCircus\Cortex`, `CortexServer`); `register()` merges the config, calls `registerPackage()`, then registers the domain providers. `boot()` uses `registerPolicies()`, `registerNotFoundResponses()`, `loadTranslationsFrom()` (the redirect-domain validation message only), `registerMcpServer()` and `loadHistoryRoutes()`. The history route and tool are offered only while Cortex audits (Keystone's `Package::audits()`: refactor-circus/keen installed and `cortex.audit.enabled` on); Cortex has no audit code of its own. It does not call `registerCortex()`: Cortex registers its own server with its own registry, and its management tools are not offered to agents.
- Domain providers extend `RefactorCircus\Keystone\Support\ServiceProvider`; routes load through `loadApiRoutesFrom()`, gated by `cortex.routes.enabled`.
- Events implement `RefactorCircus\Keystone\Contracts\{ActionStartingEvent,ActionFinishedEvent,ModelLifecycleEvent}`; every Action dispatches its starting event, does its writes inside one `DB::transaction()` and dispatches its finished event after it (cache invalidation waits for the commit with `DB::afterCommit()`). Models use `RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents` with a `{Entity}{Hook}Event` for every Eloquent hook and resolve their table with `getTable()` from `Support\Tables` (`cortex.tables`); migrations, pivots, `Rule::unique`/`exists` (by model class) and raw queries use the same keys, never literal `cortex_*` names. Every model has a policy (`Domains/{Domain}/Policies`, on `Support\Policies\Policy`, registered from `cortex.policies` by `registerPolicies()`). `tests/ArchTest.php` checks action events, transactions, model events, literal table names (`literalTableNames()`) and models without policies (`modelsWithoutPolicies()`) with Keystone's `Testing\Arch`; `tests/Modes/RenamedTables` re-runs representative flows with every table renamed.
- Actions extend `RefactorCircus\Keystone\Actions\Action`: callers use `execute()`, each action implements a `protected handle()` with a concrete return type, and `tests/ArchTest.php` checks the base. Every exception extends the abstract `RefactorCircus\Cortex\Exceptions\CortexException` (on Keystone's `PackageException`), also checked by `tests/ArchTest.php`; never throw a raw SPL exception.
- Action input is a `final readonly` `{Action}Data` in the domain's `Data/` (on `RefactorCircus\Keystone\Data\Data`), never an array: fields are promoted constructor properties (camelCase of the input keys; `T|Missing = new Missing` for partial updates), `rules()` lives on the Data (with any context args, e.g. `UpdateConcreteAgentToolsData::rules($agent)`), and the starting/finished events that took input carry the Data. List Data expose `pagination()` (Keystone's `Pagination`) and add its rules; their tools add `ListXData::pagination()->schema($schema)`. HTTP and MCP requests validate once: their `rules()` return the identifier rules (`agentRules()`, `versionRules()`, `serverRules()`, `ownerRules()`, `domainRules()` on the MCP and redirect-domain bases) plus `XData::rules(...)`, and `persist()`/`handle()` build with `XData::fromValidated($this->validated() / $validated)` - never `XData::from()` in a request (ArchTest). `XData::from($input, ...context)` validates untrusted input outside a request; code that vouches for its values uses `new XData(...)`.
- API resources extend `RefactorCircus\Keystone\Http\Resources\JsonResource` and declare `schema(JsonSchema $schema)` next to `toArray()` (always-present fields `required()`, conditional ones not, `nullable()` where null is possible). MCP requests answer with `(new XResource($m))->toStructuredResponse()` / `XResource::collection($page)->toStructuredResponse()`, and each tool that answers with structured content declares `outputSchema()` as `XResource::structuredSchema($schema)` / `collectionSchema($schema, ListXData::pagination())` (unpaginated lists declare `data` alone). `mcpTool()` in tests is Keystone's `Testing\McpTools::call()`, which checks every structured answer against the tool's schema, and `tests/Feature/Mcp/OutputSchemaTest.php` requires the declaration.
- `Http\Request` and `Mcp\Request` extend Keystone's request bases but keep Cortex's authorization: every check goes to the Gate, as the signed-in user or as a guest, and the bundled policies allow guests. Do not switch them to Keystone's `authorization` switch. A guest refused over HTTP gets 401 (Keystone's base), a signed-in user 403. `ConcreteAgentMcpRequest` and `ServerMcpRequest` throw `ModelNotFoundException` for unknown names on purpose, so they answer the base request's `Not found.`.
- `Mcp\CortexServer` extends `RefactorCircus\Keystone\Mcp\Server` and lists every tool in `TOOLS`, including `Mcp\Tools\ListCortexHistoryTool`. Cortex's own tools extend `RefactorCircus\Keystone\Mcp\Tool`; `Domains\Tool\Support\Tool` and `Domains\ConcreteServer\Support\Server` are for application tools and servers.

## API description and client

Front ends, Atrium v2 first (Cortex's own module in `atrium/` included), use the JSON API through two committed artifacts:

- `openapi.json` at the package root describes every route named `cortex.*` (paths relative to `cortex.routes.prefix`, `info.version` `2.0.0`). `composer openapi:export` writes it from the workbench with Scramble (`dedoc/scramble`, require-dev only, loaded by `testbench.yaml`); `WorkbenchServiceProvider` pins the prefix, the `cortex.` route filter, the version and the description so the export is deterministic. Never edit it by hand.
- `client/` is `@refactor-circus/cortex-client`, TypeScript source only (no build, not on npm): `src/index.ts` exports `createCortexClient(options)` (openapi-fetch's `Client<paths>`) and the `paths`, `components` and `operations` types; `src/schema.d.ts` is generated by openapi-typescript and its first line holds the sha256 of the `openapi.json` it came from.
- After adding or changing a route, run `composer client:generate` (export, `npm ci`, regenerate, type-check; needs Node) and commit `openapi.json` and `client/src/schema.d.ts` together. `tests/Feature/OpenApiTest.php` fails while either is stale, so `composer test` itself needs no Node.
- The UI-facing reads are ordinary endpoints with MCP tools: `cortex.search.index` (`search-agents-tool`), `cortex.runnable-agents.index` (`list-runnable-agents-tool`) and `cortex.settings-summary.show` (`show-settings-summary-tool`). A new screen need in Atrium v2 becomes an endpoint (and MCP tool) here, never a view.

## Quick Commands

- Full validation: `composer test`
- Formatting check: `composer lint:check`
- Static analysis: `composer analyse`
- Pest tests: `composer test:unit`
- Workbench build: `composer build`
- Workbench server: `composer serve`

## Local Skills

- `package-scaffold`: use when adding package capabilities or wiring them through the service provider, including commands, migrations, routes, config, views, translations, assets, middleware, publish tags, workbench files, and console-only behavior.
- `package-testing`: use when adding or changing package tests with Pest 4 and Orchestra Testbench.
- `package-release`: use when preparing changelog, release notes, tags, or GitHub release workflow changes.
- `package-compatibility`: use when reviewing code, dependencies, or CI against the PHP and Laravel support matrix.
- `package-generate-skill`: use when updating the bundled Boost skill from the package implementation, README, and examples.

<!-- ring-master:start -->
## Ring Master

- Specs, product docs and local standards live in `docs/agent/`. Shared standards come from `vendor/` through `docs/agent/standards.index.yml`; read the matching standards before changing code.
- Workflow: `/ring-master-shape-spec` → `/ring-master-write-spec` → `/ring-master-create-tasks` → `/ring-master-implement` → `/ring-master-verify-spec`.
- Done means `composer test` passes. When work must break a standard, add a row to `docs/agent/standards-feedback.md`.
- Keep this checkout on `main`: other packages' jobs build against it. Never switch branches, commit or stash here; do any change that is not a factory job (which makes its own) in a worktree beside the package: `git worktree add ../<Package>--<slug> -b <branch> origin/main`, removed once it is merged.
<!-- ring-master:end -->
