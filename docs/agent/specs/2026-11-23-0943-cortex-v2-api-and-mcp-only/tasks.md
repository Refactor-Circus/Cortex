# Tasks: Cortex v2: API and MCP only

Groups run in order; each leaves `vendor/bin/pest` green.

## Group 1: Agent search endpoint
depends on: none
files: src/Domains/VirtualAgent/Actions/SearchAgentsAction.php, src/Domains/VirtualAgent/Data/SearchAgentsData.php, src/Domains/VirtualAgent/Events/AgentsSearchingActionEvent.php, src/Domains/VirtualAgent/Events/AgentsSearchedActionEvent.php, src/Domains/VirtualAgent/Http/Requests/SearchAgentsRequest.php, src/Domains/VirtualAgent/Http/Resources/AgentReferenceResource.php, src/Domains/VirtualAgent/Http/Controllers/AgentSearchController.php, src/Domains/VirtualAgent/routes.php, tests/Feature/SearchAgentsActionsTest.php, tests/Feature/Http/SearchAgentsTest.php
criteria: 1, 2, 5

- [x] 1.1 Write tests: action test (virtual by name and slug, wildcard `%`/`_` matched literally, 5-per-kind limit, ordering, concrete case-insensitive match, `view` filtering per result with a denying test policy, override lookup in one query, events dispatched); HTTP test (`cortex.search.index` payload shape, 422 without `q` and over 255 chars, 401 guest / 403 user when `viewAny` denied on both models, one half only when one `viewAny` denied)
- [x] 1.2 Add `SearchAgentsData` (`query`, rules `q` required string max 255 mapped from input `q`), the event pair and `SearchAgentsAction::handle(SearchAgentsData, ?Authenticatable $user)` returning `list<array{type,key,name}>`; port the logic from `src/Atrium/CortexPlugin::search()` without URLs or labels
- [x] 1.3 Add final `AgentReferenceResource` (model-less, `schema()` with required `type`, `key`, `name`), `SearchAgentsRequest` (authorize `viewAny` on either model; `rules()` from the Data; `persist()` via `fromValidated`), `AgentSearchController@index`, and route `GET search` named `search.index` in VirtualAgent `routes.php`
- [x] 1.4 Run vendor/bin/pest; all green

## Group 2: Runnable agents endpoint
depends on: 1
files: src/Domains/VirtualAgent/Actions/ListRunnableAgentsAction.php, src/Domains/VirtualAgent/Events/RunnableAgentsListingActionEvent.php, src/Domains/VirtualAgent/Events/RunnableAgentsListedActionEvent.php, src/Domains/VirtualAgent/Http/Requests/IndexRunnableAgentsRequest.php, src/Domains/VirtualAgent/Http/Controllers/RunnableAgentController.php, src/Domains/VirtualAgent/routes.php, tests/Feature/ListRunnableAgentsActionsTest.php, tests/Feature/Http/RunnableAgentsTest.php
criteria: 3, 5

- [x] 2.1 Write tests: action test (virtual by name then concrete sorted, `run` filtering for both kinds incl. concrete agents without an override, overrides in one query, events); HTTP test (`cortex.runnable-agents.index` shape, unpaginated, 401/403 when `viewAny` denied on both, `data: []` when nothing is runnable)
- [x] 2.2 Add the event pair and `ListRunnableAgentsAction::handle(?Authenticatable $user)` ported from `src/Atrium/RunnableAgents::options()` (unsaved `ConcreteAgentOverrideModel(['agent' => $name])` when no override exists), returning the AgentReferenceResource item shape
- [x] 2.3 Add `IndexRunnableAgentsRequest`, `RunnableAgentController@index` and route `GET runnable-agents` named `runnable-agents.index`
- [x] 2.4 Run vendor/bin/pest; all green

## Group 3: Settings summary endpoint
depends on: 2
files: src/Domains/VirtualAgent/Actions/ShowSettingsSummaryAction.php, src/Domains/VirtualAgent/Events/SettingsSummaryShowingActionEvent.php, src/Domains/VirtualAgent/Events/SettingsSummaryShownActionEvent.php, src/Domains/VirtualAgent/Http/Requests/ShowSettingsSummaryRequest.php, src/Domains/VirtualAgent/Http/Resources/SettingsSummaryResource.php, src/Domains/VirtualAgent/Http/Controllers/SettingsSummaryController.php, src/Domains/VirtualAgent/routes.php, tests/Feature/ShowSettingsSummaryActionsTest.php, tests/Feature/Http/SettingsSummaryTest.php
criteria: 4, 5

- [x] 3.1 Write tests: action test (providers from `cortex.providers` as `[{name, models}]`, `[]` when unset, counts equal registry `names()` counts after registering extra tools/agents/servers, `cache_enabled` follows config, events); HTTP test (`cortex.settings-summary.show` JSON paths and types, reachable as a guest like `providers.index`)
- [x] 3.2 Add the event pair and `ShowSettingsSummaryAction` ported from `CortexPlugin::settings()`
- [x] 3.3 Add final `SettingsSummaryResource` with `schema()`, `ShowSettingsSummaryRequest`, `SettingsSummaryController@show` and route `GET settings-summary` named `settings-summary.show`
- [x] 3.4 Run vendor/bin/pest; all green

## Group 4: MCP tools for the new endpoints
depends on: 3
files: src/Domains/VirtualAgent/Mcp/Tools/SearchAgentsTool.php, src/Domains/VirtualAgent/Mcp/Tools/ListRunnableAgentsTool.php, src/Domains/VirtualAgent/Mcp/Tools/ShowSettingsSummaryTool.php, src/Domains/VirtualAgent/Mcp/Requests/SearchAgentsMcpRequest.php, src/Domains/VirtualAgent/Mcp/Requests/ListRunnableAgentsMcpRequest.php, src/Domains/VirtualAgent/Mcp/Requests/ShowSettingsSummaryMcpRequest.php, src/Mcp/CortexServer.php, tests/Feature/Mcp/AgentDirectoryToolsTest.php
criteria: 6

- [x] 4.1 Write tests with `mcpTool()`: each tool's structured content equals the HTTP payload of `cortex.search.index`, `cortex.runnable-agents.index`, `cortex.settings-summary.show`; list tools also for `data: []`; search refuses a missing `q`; refusal when `viewAny` denied on both models; tools listed in `CortexServer::TOOLS` (OutputSchemaTest covers the declarations)
- [x] 4.2 Add the three McpRequests (on `Mcp\Request`, same actions and `SearchAgentsData::rules()`/`fromValidated`, answering `AgentReferenceResource::collection(...)->toStructuredResponse()` / `(new SettingsSummaryResource(...))->toStructuredResponse()`)
- [x] 4.3 Add the three tools on Keystone's `Mcp\Tool` with described schemas and `outputSchema()` (`data` alone for the unpaginated lists), and list them in `CortexServer::TOOLS`
- [x] 4.4 Run vendor/bin/pest; all green

## Group 5: Remove the Atrium UI
depends on: 4
files: src/Atrium/** (delete), resources/views/** (delete), tests/Feature/Ui/** (delete), tests/WithoutAtrium/** (delete), tests/Modes/PennantPlus/** (delete), tests/PennantPlusTestCase.php (delete), tests/Fixtures/views/screen-probe.blade.php (delete), tests/Feature/HistoryNavigationTest.php (delete), tests/Feature/NavigationSectionTest.php (delete), tests/Feature/NoHistoryUiTest.php (delete), phpunit.without-atrium.xml (delete), workbench/app/Http/Controllers/TestHostScreenController.php (delete), workbench/resources/views/test-host.blade.php (delete), src/CortexServiceProvider.php, src/Domains/ConcreteServer/Policies/McpInstructionPolicy.php, src/Domains/RedirectDomain/Policies/RedirectDomainPolicy.php, config/cortex.php, lang/en/cortex.php, composer.json, testbench.yaml, phpunit.xml.dist, .github/workflows/tests.yml, workbench/app/Providers/WorkbenchServiceProvider.php, tests/TestCase.php, tests/Pest.php, tests/ArchTest.php, tests/Feature/ComposerTest.php, tests/Feature/DocsTest.php, tests/Feature/ExampleTest.php, tests/Feature/LockedToolsTest.php, tests/Feature/PerformanceTest.php, tests/Feature/RedirectDomainsTest.php, tests/Feature/ToolTagsTest.php, tests/Feature/NoRosterReferenceTest.php, tests/Feature/Mcp/McpRegistrationTest.php, tests/Feature/Mcp/ServerToolOverrideTest.php, tests/Feature/NoAtriumTest.php
criteria: 7, 8, 9, 10, 11, 12

- [x] 5.1 Write tests first: `tests/Feature/NoAtriumTest.php` (no `atrium`/`pennantplus` text in `src/`, `config/`, `lang/`, `workbench/`, `composer.json`, `testbench.yaml`, `phpunit.xml.dist`, `.github/workflows/`; no `atrium.*` route; `config('cortex.ui')` and `config('cortex.atrium')` null; no `cortexCan` directive; no `cortex-views` publish tag; every `lang/en/cortex.php` key referenced in `src/`); rewrite `ComposerTest` for AC 11 (no Atrium/PennantPlus anywhere in composer.json, no `extra.atrium`, no `test:without-atrium`, Keystone the only suite `require`); `ArchTest`: `src` uses no `RefactorCircus\Atrium`, `RefactorCircus\PennantPlus` or `Dedoc`
- [x] 5.2 Delete the files marked (delete) above; in mixed test files remove only the cases hitting `atrium.*` routes or Atrium classes (LockedTools dashboard case, Performance UI cases, RedirectDomains UI cases, ToolTags UI cases, ScreenExtensions resets in the two MCP tests); `ExampleTest` drops the `cortex.ui.enabled` and views assertions; `DocsTest` drops its Atrium README/conflict checks; keep the owner-filter case in `RedirectDomainsTest` (AC 7)
- [x] 5.3 `CortexServiceProvider`: drop `registerAtriumPlugin()`, `registerAtriumScreens()`, `loadViewsFrom()`, `Blade::if('cortexCan')` and the `cortex-views` publish; keep translations; fix the two policy docblocks
- [x] 5.4 `config/cortex.php`: remove the `ui` and `atrium` sections and the `CortexSupportFeature` import; Auditing banner stops naming Keen; trim `lang/en/cortex.php` to keys used in `src/`
- [x] 5.5 `composer.json`: remove Atrium and PennantPlus from require-dev, suggest, conflict, repositories, `extra.atrium`, the `test:without-atrium` script and its `test` entry; `testbench.yaml`: drop the Atrium provider, `start: /atrium`, `atrium-assets`; `phpunit.xml.dist` drops the WithoutAtrium comment/exclusion; `tests.yml` drops the `without-atrium` job; `tests/TestCase.php` drops `AtriumServiceProvider` and the test-host route; `tests/Pest.php` drops `ResetsScreenExtensions`, PennantPlus and WithoutAtrium; `WorkbenchServiceProvider` drops the `viewAtrium` gate
- [x] 5.6 Run vendor/bin/pest; all green (and `composer analyse`)

## Group 6: OpenAPI export and TypeScript client
depends on: 5
files: composer.json, testbench.yaml, workbench/app/Providers/WorkbenchServiceProvider.php, config/cortex.php, openapi.json, client/package.json, client/package-lock.json, client/tsconfig.json, client/.gitignore, client/scripts/generate.mjs, client/src/index.ts, client/src/schema.d.ts, tests/Feature/OpenApiTest.php
criteria: 13, 14, 15, 16

- [ ] 6.1 Write `tests/Feature/OpenApiTest.php`: re-export via `Process` (`php vendor/bin/testbench scramble:export --path=<tmp>`) equals committed `openapi.json`; `info.version` is `2.0.0`; the document's operations (method + path) equal the registered `cortex.*` routes, `search`, `runnable-agents` and `settings-summary` included; first line of `client/src/schema.d.ts` holds sha256 of `openapi.json`; `client/package.json` has name `@refactor-circus/cortex-client`, version `2.0.0`, the deps of AC 15; `client/src/index.ts` exports `createCortexClient`; `dedoc/scramble` in require-dev only
- [ ] 6.2 Add `dedoc/scramble` to require-dev, `Dedoc\Scramble\ScrambleServiceProvider` to `testbench.yaml`, and Scramble config in `WorkbenchServiceProvider` (api path from `cortex.routes.prefix`, routes filtered to `cortex.*` names, version `2.0.0`, fixed description); composer scripts `openapi:export` and `client:generate`
- [ ] 6.3 Create `client/` (package.json, tsconfig, .gitignore, `scripts/generate.mjs` writing the sha256 header, `src/index.ts` with `createCortexClient` and type re-exports); run `composer client:generate` and commit `openapi.json`, `client/package-lock.json` and `client/src/schema.d.ts`
- [ ] 6.4 Routes banner in `config/cortex.php` names `openapi.json`, `client/` and `composer client:generate`
- [ ] 6.5 Run vendor/bin/pest; all green

## Group 7: Docs and local standards
depends on: 6
files: README.md, CHANGELOG.md, resources/boost/skills/cortex-development/SKILL.md, CLAUDE.md, AGENTS.md, .github/CONTRIBUTING.md, docs/agent/standards/cortex/atrium-screens.md (delete), docs/agent/standards/cortex/domains.md, docs/agent/standards/cortex/optional-surfaces.md, docs/agent/standards/cortex/testbench-environment.md, tests/Feature/DocsTest.php
criteria: 17, 18, 19, 20

- [ ] 7.1 Extend `DocsTest`: CHANGELOG `v2.0.0` section names the removed pieces and the new endpoints/tools/client (AC 17 keywords); README, Boost skill, CLAUDE.md, AGENTS.md mention `cortex.search.index`, `cortex.runnable-agents.index`, `cortex.settings-summary.show`, `openapi.json`, `@refactor-circus/cortex-client`, `client:generate`, Atrium v2, and contain no `cortex.ui`, `cortex.atrium` or `## Dashboard`; routes banner check; `atrium-screens.md` absent and the three standards free of "Atrium"
- [ ] 7.2 CHANGELOG `## [v2.0.0]` section with upgrade notes (D-013)
- [ ] 7.3 README: remove Dashboard and "Redirect domains on other screens" Atrium content and the without-Atrium testing notes; add API endpoints, OpenAPI and TypeScript client sections; Atrium v2 as optional UI. Boost skill, CLAUDE.md (Layout, Keystone boot list, Frontend section replaced), AGENTS.md and CONTRIBUTING updated to match
- [ ] 7.4 Delete `cortex/atrium-screens.md`; update `cortex/domains.md` (no `src/Atrium`, CortexSupportFeature stored-name line gone, new VirtualAgent reads), `cortex/optional-surfaces.md` (no dashboard row), `cortex/testbench-environment.md` (provider order Ai, Mcp, Cortex)
- [ ] 7.5 Run `composer test` (vendor/bin/pest included); all green
