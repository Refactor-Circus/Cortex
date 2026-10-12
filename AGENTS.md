# Cortex

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `refactor-circus/cortex`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Layout

The package follows the mono domain-module layout, described in `agent-os/standards/backend/domain-modules.md`. Code lives in `src/Domains/{VirtualAgent,ConcreteAgent,Tool,McpServer,RedirectDomain}` (namespace `RefactorCircus\Cortex\Domains\{Domain}`), each with its own `{Domain}ServiceProvider` registered by `Domains\DomainServiceProvider`. Models are named `{Entity}Model`. The Atrium screens span every domain and live in `src/Atrium`; package-wide pieces (`Cortex`, the facade, the base requests, `Mcp\CortexServer`, `Support\`) stay at the top level. `config/cortex.php` stays one file.

## Keystone

Cortex stands on `refactor-circus/keystone`, the shared runtime of the Refactor Circus suite; read its README before changing base classes.

- `CortexServiceProvider` extends `RefactorCircus\Keystone\Support\PackageServiceProvider`. `definition()` describes the package (`cortex`, `RefactorCircus\Cortex`, `CortexServer`); `register()` merges the config, calls `registerPackage()`, then registers the domain providers. `boot()` uses `registerPolicies()`, `registerNotFoundResponses()`, `registerAtriumPlugin()`, `registerMcpServer()` and `loadHistoryRoutes()`. The history route and tool are offered only while Cortex audits (Keystone's `Package::audits()`: refactor-circus/keen installed and `cortex.audit.enabled` on); Cortex has no audit code of its own. It does not call `registerCortex()`: Cortex registers its own server with its own registry, and its management tools are not offered to agents.
- Domain providers extend `RefactorCircus\Keystone\Support\ServiceProvider`; routes load through `loadApiRoutesFrom()`, gated by `cortex.routes.enabled`.
- Events implement `RefactorCircus\Keystone\Contracts\{ActionStartingEvent,ActionFinishedEvent,ModelLifecycleEvent}`; every Action dispatches its starting event, does its writes inside one `DB::transaction()` and dispatches its finished event after it (cache invalidation waits for the commit with `DB::afterCommit()`). Models use `RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents` with a `{Entity}{Hook}Event` for every Eloquent hook and resolve their table with `getTable()` from `Support\Tables` (`cortex.tables`); migrations, pivots, `Rule::unique`/`exists` (by model class) and raw queries use the same keys, never literal `cortex_*` names. Every model has a policy (`Domains/{Domain}/Policies`, on `Support\Policies\Policy`, registered from `cortex.policies` by `registerPolicies()`). `tests/ArchTest.php` checks action events, transactions, model events, literal table names (`literalTableNames()`) and models without policies (`modelsWithoutPolicies()`) with Keystone's `Testing\Arch`; `tests/Modes/RenamedTables` re-runs representative flows with every table renamed.
- Action input is a `final readonly` `{Action}Data` in the domain's `Data/` (on `RefactorCircus\Keystone\Data\Data`), never an array: fields are promoted constructor properties (camelCase of the input keys; `T|Missing = new Missing` for partial updates), `rules()` lives on the Data (with any context args, e.g. `UpdateConcreteAgentToolsData::rules($agent)`), and the starting/finished events that took input carry the Data. List Data expose `pagination()` (Keystone's `Pagination`) and add its rules; their tools add `ListXData::pagination()->schema($schema)`. HTTP and MCP requests validate once: their `rules()` return the identifier rules (`agentRules()`, `versionRules()`, `serverRules()`, `ownerRules()`, `domainRules()` on the MCP and redirect-domain bases) plus `XData::rules(...)`, and `persist()`/`handle()` build with `XData::fromValidated($this->validated() / $validated)` - never `XData::from()` in a request (ArchTest). Atrium controllers use `XData::from($request->all(), ...context)`, which validates; code that vouches for its values uses `new XData(...)`.
- API resources extend `RefactorCircus\Keystone\Http\Resources\JsonResource` and declare `schema(JsonSchema $schema)` next to `toArray()` (always-present fields `required()`, conditional ones not, `nullable()` where null is possible). MCP requests answer with `(new XResource($m))->toStructuredResponse()` / `XResource::collection($page)->toStructuredResponse()`, and each tool that answers with structured content declares `outputSchema()` as `XResource::structuredSchema($schema)` / `collectionSchema($schema, ListXData::pagination())` (unpaginated lists declare `data` alone). `mcpTool()` in tests is Keystone's `Testing\McpTools::call()`, which checks every structured answer against the tool's schema, and `tests/Feature/Mcp/OutputSchemaTest.php` requires the declaration.
- `Http\Request` and `Mcp\Request` extend Keystone's request bases but keep Cortex's authorization: every check goes to the Gate, as the signed-in user or as a guest, and the bundled policies allow guests. Do not switch them to Keystone's `authorization` switch. A guest refused over HTTP gets 401 (Keystone's base), a signed-in user 403. `ConcreteAgentMcpRequest` and `ServerMcpRequest` throw `ModelNotFoundException` for unknown names on purpose, so they answer the base request's `Not found.`.
- `Mcp\CortexServer` extends `RefactorCircus\Keystone\Mcp\Server` and lists every tool in `TOOLS`, including `Mcp\Tools\ListCortexHistoryTool`. Cortex's own tools extend `RefactorCircus\Keystone\Mcp\Tool`; `Domains\Tool\Support\Tool` and `Domains\McpServer\Support\Server` are for application tools and servers.

## Frontend

Atrium owns every component and style; Cortex ships no `resources/css`, no `Atrium::css()` call and no component namespace. See `agent-os/standards/frontend/atrium-screens.md`.

- Views use only `x-atrium::*` components and the utilities Atrium safelists. No `<style>` blocks and no `style=` attributes. `tests/Feature/Ui/StylesTest.php` checks this with `RefactorCircus\Atrium\Testing\AtriumStyles`.
- Status and errors use `<x-atrium::flash :keys="['prompt', 'agent']" />`; tag filters are `x-atrium::chip`s; searches are `x-atrium::search-input`.
- History is `<x-atrium::audit-trail source="cortex" />`, with `:subject` on a record's screen. The content-versions partial stays, since versions are publishable content.
- `CortexPlugin::features()` uses Atrium's `featuresFromConfig()`. Cortex keeps its own `Atrium\ScreenAccess` and `AuthorizesScreens`: Atrium's copies follow Keystone's `authorization` switch and refuse guests, while Cortex asks the Gate as the signed-in user or as a guest, with extra arguments.

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
