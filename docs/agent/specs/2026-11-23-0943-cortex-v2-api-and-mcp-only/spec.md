# Cortex v2: API and MCP only

## Goal

Cortex v2.0.0 ships its JSON API and MCP server only. The Atrium screens leave this package; Atrium v2 rebuilds them on Cortex's API through a committed OpenAPI file and a generated TypeScript client. The three things the Blade UI computed in PHP with no API route (agent search, the run form's agent list and the settings panel's summary) become endpoints and MCP tools.

## Acceptance criteria

New API (decisions D-003 to D-007)

1. `GET {prefix}/search?q=` (route `cortex.search.index`) answers `{"data": [...]}`: up to 5 virtual agents whose name or slug contains `q` (SQL `LIKE` wildcards in `q` matched literally), ordered by name, then up to 5 registered concrete agents whose name contains `q` case-insensitively, ordered by name. Each item is `{"type": "virtual_agent"|"concrete_agent", "key": <slug or registered name>, "name": <name>}`, serialized by `AgentReferenceResource`.
2. Search validates `q` as a required string of at most 255 characters (422 otherwise). It is refused (401 for a guest, 403 for a signed-in user) unless the caller passes `viewAny` on `VirtualAgentModel` or on `ConcreteAgentOverrideModel`; each half is searched only when its `viewAny` passes; each result is kept only when the caller passes `view` on it (a concrete agent is checked against its saved override, or an unsaved `ConcreteAgentOverrideModel` for its name when none exists, all overrides fetched in one query).
3. `GET {prefix}/runnable-agents` (route `cortex.runnable-agents.index`) answers `{"data": [...]}`, unpaginated: every virtual agent ordered by name, then every registered concrete agent ordered by name, kept only when the caller passes `run` on it (concrete agents checked as in AC 2), in the AC 1 item shape. It is refused (401/403) unless the caller passes `viewAny` on `VirtualAgentModel` or `ConcreteAgentOverrideModel`.
4. `GET {prefix}/settings-summary` (route `cortex.settings-summary.show`) answers `{"data": {"providers": [{"name": string, "models": string[]}], "tool_count": int, "agent_count": int, "server_count": int, "cache_enabled": bool}}`: `providers` from the `cortex.providers` config (`[]` when unset), the counts from `ToolRegistry`, `AgentRegistry` and `McpServerRegistry` `names()`, `cache_enabled` from `cortex.cache.enabled`. Like `cortex.providers.index` it asks no policy; the route middleware is its gate.
5. Each new endpoint is a controller that only returns `$request->persist()`, a `{Verb}{Entity}Request` on `Http\Request`, one action on Keystone's `Action` dispatching a starting and a finished `ActionEvent`, and a final resource declaring `schema()`. `tests/ArchTest.php` passes unchanged in its rules for these.
6. MCP tools `SearchAgentsTool`, `ListRunnableAgentsTool` and `ShowSettingsSummaryTool` are in `CortexServer::TOOLS`, call the same actions with the same Data, declare `outputSchema()`, and each has a parity test: its structured content equals the HTTP payload of AC 1, 3 and 4 (list tools also for the empty `data: []` case).
7. `GET {prefix}/redirect-domains?owner_type=&owner_id=` (`cortex.redirect-domains.index`) still lists only that owner's domains; its existing test keeps passing after the UI is removed.

Atrium removal

8. `src/Atrium/`, `resources/views/`, `tests/Feature/Ui/`, `tests/WithoutAtrium/`, `tests/Modes/PennantPlus/`, `tests/PennantPlusTestCase.php`, `tests/Fixtures/views/screen-probe.blade.php`, `phpunit.without-atrium.xml`, the workbench test host (`TestHostScreenController`, `test-host.blade.php`) and the Atrium-only tests (`HistoryNavigationTest`, `NavigationSectionTest`, `NoHistoryUiTest`) no longer exist; tests in mixed files that hit `atrium.*` routes are removed.
9. No file under `src/`, `config/`, `lang/`, `workbench/` or in `composer.json`, `testbench.yaml`, `phpunit.xml.dist` or `.github/workflows/` contains `atrium` or `pennantplus` (case-insensitive), proven by a test; `tests/ArchTest.php` asserts `src` uses no `RefactorCircus\Atrium`, `RefactorCircus\PennantPlus` or `Dedoc` class.
10. `CortexServiceProvider` registers no Atrium plugin or screens, no views, no `cortexCan` Blade directive and no `cortex-views` publish tag; no route named `atrium.*` is registered; `config('cortex.ui')` and `config('cortex.atrium')` are null.
11. `composer.json` has no `refactor-circus/atrium` or `refactor-circus/pennantplus` in `require`, `require-dev`, `suggest`, `conflict` or `repositories`, no `extra.atrium`, no `test:without-atrium` script (nor in `test`); `refactor-circus/keystone` is the only `refactor-circus/*` package in `require`. The CI workflow has no `without-atrium` job.
12. `lang/en/cortex.php` holds only keys referenced from `src/`, proven by a test.

OpenAPI and client

13. `dedoc/scramble` is in `require-dev` only, and its provider is loaded by `testbench.yaml` for the workbench only. `composer openapi:export` writes `openapi.json` at the package root from the workbench; it lists every registered route named `cortex.*` (method and path) and no other, and `info.version` is `2.0.0`.
14. A Pest test exports the OpenAPI document again to a temporary file and fails when it differs from the committed `openapi.json`.
15. `client/` is committed with `package.json` (`"name": "@refactor-circus/cortex-client"`, `"version": "2.0.0"`, dependency `openapi-fetch`, dev dependencies `openapi-typescript` and `typescript`, `exports`/`types` pointing at `src/index.ts`), a lockfile, `src/schema.d.ts` generated by `openapi-typescript` from `openapi.json`, and `src/index.ts` exporting `createCortexClient(options)` that returns openapi-fetch's `Client<paths>` and re-exporting the `paths`, `components` and `operations` types.
16. The first line of `client/src/schema.d.ts` carries the sha256 of the `openapi.json` it was generated from; a Pest test fails when it differs from the committed file's hash. `composer client:generate` runs `openapi:export`, installs the client's packages, regenerates `schema.d.ts` with that header and type-checks the client.

Docs

17. `CHANGELOG.md` has a `v2.0.0` section whose upgrade notes cover: the Atrium UI removed (use Atrium v2, `atrium.integrations.cortex.*`), the removed keys `cortex.ui.*` and `cortex.atrium.*`, `CortexSupportFeature`, the `cortex::ui.*` views, the `cortex-views` publish tag and the `atrium.cortex.*` routes; the three new endpoints and MCP tools; `openapi.json` and `client/`.
18. `README.md`, the Boost skill, `CLAUDE.md` and `AGENTS.md` describe Cortex as JSON API + MCP with Atrium v2 as the optional UI, document the three endpoints, `openapi.json`, the client and `composer client:generate`, and have no Dashboard section or `cortex.ui`/`cortex.atrium` key; `tests/Feature/DocsTest.php` checks these keywords. The routes banner in `config/cortex.php` names `openapi.json` and `client/`.
19. The local standard `cortex/atrium-screens.md` is deleted; `cortex/domains.md`, `cortex/optional-surfaces.md` and `cortex/testbench-environment.md` no longer mention Atrium.
20. `composer test` (`vendor/bin/pest` included) passes.

## Design

### New endpoints (VirtualAgent domain, D-006)

| Method | URI | Name | Controller | Request | Action | Resource | MCP tool |
| --- | --- | --- | --- | --- | --- | --- | --- |
| GET | `{prefix}/search` | `cortex.search.index` | `AgentSearchController@index` | `SearchAgentsRequest` | `SearchAgentsAction` (`SearchAgentsData{query}`) | `AgentReferenceResource` collection | `SearchAgentsTool` (`search-agents-tool`) |
| GET | `{prefix}/runnable-agents` | `cortex.runnable-agents.index` | `RunnableAgentController@index` | `IndexRunnableAgentsRequest` | `ListRunnableAgentsAction` | `AgentReferenceResource` collection | `ListRunnableAgentsTool` (`list-runnable-agents-tool`) |
| GET | `{prefix}/settings-summary` | `cortex.settings-summary.show` | `SettingsSummaryController@show` | `ShowSettingsSummaryRequest` | `ShowSettingsSummaryAction` | `SettingsSummaryResource` | `ShowSettingsSummaryTool` (`show-settings-summary-tool`) |

- Files live under `src/Domains/VirtualAgent/`: `Actions/`, `Data/SearchAgentsData.php`, `Events/` (`AgentsSearchingActionEvent`/`AgentsSearchedActionEvent`, `RunnableAgentsListingActionEvent`/`RunnableAgentsListedActionEvent`, `SettingsSummaryShowingActionEvent`/`SettingsSummaryShownActionEvent`), `Http/{Controllers,Requests,Resources}/`, `Mcp/{Tools,Requests}/`. Routes go in `src/Domains/VirtualAgent/routes.php` beside `providers.index`.
- Actions are reads: starting event, work, finished event, no transaction (as `ListProvidersAction`). Search and runnable agents take the caller (`?Authenticatable $user`) as a second `handle()` argument and filter with `Gate::forUser($user)`; the request still checks the `viewAny` precondition in `authorize()`. They return `list<array{type: string, key: string, name: string}>`, which `AgentReferenceResource` (model-less, final) serializes. `ShowSettingsSummaryAction` returns the AC 4 array for `SettingsSummaryResource`.
- The logic moves from `CortexPlugin::search()`, `RunnableAgents::options()` and `CortexPlugin::settings()`, minus the UI parts (route URLs, translated labels).
- MCP requests follow `cortex/mcp-tools`: tools are shells on Keystone's `Mcp\Tool`; search takes `q` with `SearchAgentsData::rules()`; outputs use `AgentReferenceResource::collection(...)->toStructuredResponse()` with `data` alone (unpaginated) and `SettingsSummaryResource::structuredSchema()`.

### Atrium removal

- Delete the files of AC 8. `CortexServiceProvider` drops `registerAtriumPlugin()`, `registerAtriumScreens()`, `loadViewsFrom()`, `Blade::if('cortexCan', ...)` and the `cortex-views` publish; it keeps translations (`cortex::cortex.redirect_domain_invalid` is used by the redirect-domain action and Data).
- `config/cortex.php` loses the `ui` and `atrium` sections and the `CortexSupportFeature` import; the Auditing banner stops naming Keen (D-011).
- `composer.json` loses Atrium and PennantPlus from `require-dev`, `suggest`, `conflict`, `repositories` and `extra`, and the `test:without-atrium` script. `refactor-circus/keen` stays in `require-dev` for the history tests.
- `testbench.yaml` loses the Atrium provider, `start: /atrium` and the `atrium-assets` publish; `tests/TestCase.php` loses `AtriumServiceProvider` and the test-host route; `tests/Pest.php` loses `ResetsScreenExtensions`, PennantPlus and WithoutAtrium bindings; `WorkbenchServiceProvider` loses the `viewAtrium` gate. The workbench `Organization`/`User` models and policies stay (owned redirect-domain tests).
- `.github/workflows/tests.yml` loses the `without-atrium` job. The two policies' docblocks stop naming Atrium screens.
- `lang/en/cortex.php` keeps only keys referenced from `src/`.

### OpenAPI and client (D-009, D-010)

- `dedoc/scramble` in `require-dev`; `Dedoc\Scramble\ScrambleServiceProvider` in `testbench.yaml` providers. `WorkbenchServiceProvider` configures Scramble: `api_path` = `cortex.routes.prefix`, routes filtered to names starting with `cortex.`, `info.version` `2.0.0`, a fixed description, so the export is deterministic.
- Composer scripts: `openapi:export` = `@php vendor/bin/testbench scramble:export --path=openapi.json`; `client:generate` = `@openapi:export`, `npm --prefix client ci`, `npm --prefix client run generate`, `npm --prefix client run typecheck`.
- `client/`: `package.json`, `package-lock.json`, `tsconfig.json`, `.gitignore` (`node_modules`), `scripts/generate.mjs` (runs openapi-typescript's API on `../openapi.json`, writes `src/schema.d.ts` with first line `// openapi.json sha256: <hex>; generated, do not edit. Regenerate with: composer client:generate`), `src/schema.d.ts`, `src/index.ts`. The package ships TypeScript source, no build.
- `createCortexClient({ baseUrl, headers?, fetch?, credentials? })` wraps `createClient<paths>()` from openapi-fetch. `baseUrl` is the host's API base including its `cortex.routes.prefix` when Scramble emits prefix-relative paths.
- `tests/Feature/OpenApiTest.php`: runs `vendor/bin/testbench scramble:export --path=<tmp>` with `Process`, compares to `openapi.json` (AC 14), compares `openapi.json` operations to the registered `cortex.*` routes (AC 13), and checks the hash header (AC 16). `composer test` needs no Node.

### Config

- `cortex.routes.enabled` stays `true` by default and `keepRoutesEnabled()` stays (D-001). The routes banner adds a line naming `openapi.json` and `client/`.

## Out of scope

- Atrium v2's pages, widgets, search source, settings panel and the redirect-domain tab and section, and the `atrium.integrations.cortex.*` config: owned by `Atrium/2026-11-22-1823-atrium-v2-headless`.
- Removing `registerAtriumPlugin()` and the `ui.enabled` convention from Keystone: `Keystone/2026-11-23-0943-drop-the-atrium-plugin-hook`.
- Any other cross-package reference the shaping flagged (D-008); no new spec is opened from here.
- A `stats` endpoint (D-002), publishing the client to npm, tagging the v2.0.0 release, and changing the API's default-on or auth defaults.

## Risks and open questions

- Scramble's inference: controllers return `$request->persist()` as `JsonResponse`, and rules come from `XData::rules()`, so some request and response schemas may be loose. The ACs require routes, methods and paths, not complete schemas; tightening them (Scramble attributes or extensions in the workbench) is follow-up work.
- Scramble support for Laravel 13 / PHP 8.5 must be confirmed when the dependency is added; pick the newest stable that installs.
- Export determinism: the staleness test fails if Scramble output depends on environment (app URL, ordering). The workbench config pins those values.
- npm cannot install a git dependency from a subfolder; Atrium's layer needs pnpm's `#path:/client` or another way to consume `client/`. Recorded for the Atrium spec (D-010). Moot since D-016: Cortex's own Atrium module imports `client/src` by path.
- Breaking: hosts using the dashboard, `cortex::ui.*` views, published `cortex-views` or `CortexSupportFeature` lose them; a published config with `ui`/`atrium` keys is ignored harmlessly.
- Order: this spec waits for the Keystone hook removal; it no longer calls `registerAtriumPlugin()`, so it builds against Keystone either before or after that change.
