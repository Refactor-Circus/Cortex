# Build: Cortex v2: API and MCP only

## Group 1

Agent search endpoint: `GET {prefix}/search?q=` (`cortex.search.index`), AC 1, 2, 5.

### Files

- `src/Domains/VirtualAgent/Data/SearchAgentsData.php`: `final readonly`, field `query`, `rules()` `q` required string max 255; `prepare()` maps the `q` input onto `query`.
- `src/Domains/VirtualAgent/Events/AgentsSearchingActionEvent.php` (carries the Data and the caller), `AgentsSearchedActionEvent.php` (carries the results).
- `src/Domains/VirtualAgent/Actions/SearchAgentsAction.php`: `handle(SearchAgentsData, ?Authenticatable $user = null)` returning `list<array{type, key, name}>`. Ported from `CortexPlugin::search()` without URLs or labels. It checks `viewAny` per kind and `view` per result through `Gate::forUser($user)`. Virtual agents are matched by name or slug with `LIKE ... ESCAPE '!'`, with `!`, `%` and `_` escaped, ordered by name, at most 5. Concrete agents are registry names containing the query (`mb_strtolower`), sorted, at most 5. Their overrides are fetched in one `whereIn` query, and an agent without an override is checked against an unsaved `ConcreteAgentOverrideModel`. Read action: no transaction.
- `src/Domains/VirtualAgent/Http/Resources/AgentReferenceResource.php`: final, model-less, `schema()` with required `type` (enum `virtual_agent`/`concrete_agent`), `key`, `name`.
- `src/Domains/VirtualAgent/Http/Requests/SearchAgentsRequest.php`: `authorize()` allows `viewAny` on `VirtualAgentModel` or `ConcreteAgentOverrideModel`. `rules()` comes from the Data. `persist()` uses `fromValidated()` and returns `AgentReferenceResource::collection(...)->response()`.
- `src/Domains/VirtualAgent/Http/Controllers/AgentSearchController.php`: `index()` returns `$request->persist()`.
- `src/Domains/VirtualAgent/routes.php`: `GET search` named `search.index`.

### Tests

- `tests/Feature/SearchAgentsActionsTest.php` covers:
  - matching by name and by slug
  - `%`, `_` and `!` matched literally
  - 5 per kind, virtual agents first, each kind ordered by name
  - case-insensitive concrete match
  - per-result `view` filtering, using in-file denying policies (a virtual agent by slug, a concrete agent by its saved override)
  - overrides fetched in one query
  - both events dispatched with the Data, the caller and the results
  - `q` mapped onto `query`
- `tests/Feature/Http/SearchAgentsTest.php` covers:
  - the exact `{"data": [...]}` payload, and `data: []` when nothing matches
  - 422 without `q` and over 255 characters (255 is accepted)
  - 401 for a guest and 403 for a signed-in user when `viewAny` is denied on both models
  - only one half returned when only that half's `viewAny` passes
- Not run here: the gate runs `vendor/bin/pest` after the build. `pint --test` passes on the new files, and `phpstan analyse` passes on the new source files.

### Deviations

- The `LIKE` clauses use literal column names (`name like ? escape '!'`) through `whereRaw`, because PHPStan requires a literal string for raw SQL. `!` is the escape character because a backslash behaves differently on MySQL and SQLite.
- The test-only denying policies (`SearchHidingVirtualAgentPolicy`, `SearchHidingConcreteAgentOverridePolicy`) are declared in the action test file, as `GuestToolsWarningTest` does, instead of in `tests/Fixtures/Policies/`. That folder is outside this group's file list.
- The old `CortexPlugin::search()` neither ordered results nor escaped wildcards. The new action does both, as AC 1 and D-003 require.

### Retry fix

- The gate failed in `tests/Feature/EventsTest.php` ("gives every action exactly one start and one finish event"), which pins the number of action classes at 36. `SearchAgentsAction` makes it 37. This file is outside the group's list. It was changed because no other change makes the gate pass: the count went from 36 to 37, and `SearchAgentsAction` was added to the "starts and finishes every action once" run, so that test's count of start/finish event types went from 34 to 35. Groups 2 and 3 add actions and must bump these counts again.
- Checked here: this group's two test files, `ArchTest`, `EventsTest`, `tests/Feature/Http`, `OutputSchemaTest`, the without-Atrium suite, `phpstan`, `pint --test` and `--type-coverage --min=100` all pass. The full suite is left to the gate.

## Group 2

Runnable agents endpoint: `GET {prefix}/runnable-agents` (`cortex.runnable-agents.index`), AC 3, 5.

### Files

- `src/Domains/VirtualAgent/Events/RunnableAgentsListingActionEvent.php` (carries the caller), `RunnableAgentsListedActionEvent.php` (carries the agent references).
- `src/Domains/VirtualAgent/Actions/ListRunnableAgentsAction.php`: `handle(?Authenticatable $user = null)` returning `list<array{type, key, name}>`. Ported from `RunnableAgents::options()` without the `virtual:`/`concrete:` keys and translated labels. Every virtual agent ordered by name, then every registered concrete agent sorted by name, each kept only when `Gate::forUser($user)` allows `run`. Concrete agents are checked against their saved override, all fetched in one `whereIn` query (skipped when no agent is registered), or an unsaved `ConcreteAgentOverrideModel(['agent' => $name])`. Read action: no transaction.
- `src/Domains/VirtualAgent/Http/Requests/IndexRunnableAgentsRequest.php`: `authorize()` allows `viewAny` on `VirtualAgentModel` or `ConcreteAgentOverrideModel`; `persist()` passes the caller and returns `AgentReferenceResource::collection(...)->response()` (unpaginated: `data` only).
- `src/Domains/VirtualAgent/Http/Controllers/RunnableAgentController.php`: `index()` returns `$request->persist()`.
- `src/Domains/VirtualAgent/routes.php`: `GET runnable-agents` named `runnable-agents.index`.

### Tests

- `tests/Feature/ListRunnableAgentsActionsTest.php` covers:
  - virtual agents by name, then concrete agents sorted by name
  - `[]` when there are no agents
  - `run` filtering with in-file denying policies (a virtual agent by slug; a concrete agent with a saved override refused, one without an override checked against the unsaved stand-in and kept)
  - overrides fetched in one query
  - both events dispatched with the caller and the result
- `tests/Feature/Http/RunnableAgentsTest.php` covers:
  - the exact `{"data": [...]}` payload with 20 virtual agents and 2 concrete agents, no `links`/`meta` (unpaginated)
  - `data: []` when the caller may run nothing
  - `run` filtering per kind through the `Granted*` fixture policies
  - 401 for a guest and 403 for a signed-in user when `viewAny` is denied on both models; either `viewAny` lets the caller in
- Checked here: the two new test files, `EventsTest` and `ArchTest` pass; `pint --test` passes; `phpstan analyse` passes on the new source files. The full suite is left to the gate.

### Deviations

- `tests/Feature/EventsTest.php` is outside this group's file list. It pins the action count and the start/finish event types, so it was updated as Group 1 noted: `ListRunnableAgentsAction` added to the "starts and finishes every action once" run (35 to 36 event types) and the action count raised from 37 to 38. Group 3 must bump them again.
- The test-only denying policies (`RunnableHidingVirtualAgentPolicy`, `RunnableHidingConcreteAgentOverridePolicy`) are declared in the action test file, as in Group 1, since `tests/Fixtures/Policies/` is outside the file list.
- The old `RunnableAgents::options()` fetched every override; the action fetches only the registered agents' overrides (still one query).

## Group 3

Settings summary endpoint: `GET {prefix}/settings-summary` (`cortex.settings-summary.show`), AC 4, 5.

### Files

- `src/Domains/VirtualAgent/Events/SettingsSummaryShowingActionEvent.php` (no payload, as `ProvidersListingActionEvent`), `SettingsSummaryShownActionEvent.php` (carries the summary array).
- `src/Domains/VirtualAgent/Actions/ShowSettingsSummaryAction.php`: `handle()` returning `array{providers: list<array{name, models}>, tool_count, agent_count, server_count, cache_enabled}`. Ported from `CortexPlugin::settings()` without the panel label, description and view: `providers` maps the `cortex.providers` config (`name => models`) to `[{name, models}]` (`[]` when unset or empty), the counts are `count(names())` of the injected `ToolRegistry`, `AgentRegistry` and `McpServerRegistry` singletons, and `cache_enabled` is `(bool) config('cortex.cache.enabled', true)`. Read action: no transaction.
- `src/Domains/VirtualAgent/Http/Resources/SettingsSummaryResource.php`: final, model-less, `schema()` with every field `required()` (`providers` as an array of `{name, models}` objects).
- `src/Domains/VirtualAgent/Http/Requests/ShowSettingsSummaryRequest.php`: no `authorize()` override (the base allows; D-005), `persist()` returns `(new SettingsSummaryResource(...))->response()`.
- `src/Domains/VirtualAgent/Http/Controllers/SettingsSummaryController.php`: `show()` returns `$request->persist()`.
- `src/Domains/VirtualAgent/routes.php`: `GET settings-summary` named `settings-summary.show`.

### Tests

- `tests/Feature/ShowSettingsSummaryActionsTest.php` covers:
  - configured providers as `[{name, models}]`, in config order
  - `[]` when `cortex.providers` is empty or null
  - counts equal each registry's `names()` count after registering a tool, two agents and a server, and each rises by one when one more of each is registered
  - `cache_enabled` following `cortex.cache.enabled` (true and false)
  - both events dispatched, the shown event carrying the result
- `tests/Feature/Http/SettingsSummaryTest.php` covers:
  - the exact `{"data": {...}}` payload with a configured provider, registered tool/agent/server and the cache off
  - the JSON types of every field
  - `data.providers` is `[]` when none are configured
  - reachable as a guest (as `cortex.providers.index` is) and as a signed-in user
  - the route path `{prefix}/settings-summary`
- Checked here: the two new test files, `EventsTest` and `ArchTest` pass; `pint --test` passes; `phpstan analyse` passes on the new source files. The full suite is left to the gate.

### Deviations

- `tests/Feature/EventsTest.php` is outside this group's file list. As in Groups 1 and 2, it was updated for the new action: `ShowSettingsSummaryAction` added to the "starts and finishes every action once" run (36 to 37 event types) and the action count raised from 38 to 39.

### Retry check

- The earlier commit already held every task of this group, so no code changed on retry. Re-checked: the two new test files, `EventsTest`, `ArchTest`, `tests/Feature/Http`, `OutputSchemaTest`, `DocsTest`, `ToolsMigrationTest` and the without-Atrium suite pass. `phpstan analyse` (whole package), `pint --test` and `--type-coverage --min=100` pass too. The full parallel suite is left to the gate.

## Group 4

MCP tools for the new endpoints, AC 6.

### Files

- `src/Domains/VirtualAgent/Mcp/Requests/SearchAgentsMcpRequest.php`: `authorize()` allows `viewAny` on `VirtualAgentModel` or `ConcreteAgentOverrideModel`. `rules()` is `SearchAgentsData::rules()`. `handle()` runs `SearchAgentsAction` with `SearchAgentsData::fromValidated($validated)` and the caller (`$this->user()`), and answers `AgentReferenceResource::collection(...)->toStructuredResponse()`.
- `src/Domains/VirtualAgent/Mcp/Requests/ListRunnableAgentsMcpRequest.php`: the same `viewAny` precondition. `handle()` runs `ListRunnableAgentsAction` with the caller and answers `AgentReferenceResource::collection(...)->toStructuredResponse()`.
- `src/Domains/VirtualAgent/Mcp/Requests/ShowSettingsSummaryMcpRequest.php`: no `authorize()` override (D-005). It answers `(new SettingsSummaryResource(ShowSettingsSummaryAction::execute()))->toStructuredResponse()`.
- `src/Domains/VirtualAgent/Mcp/Tools/SearchAgentsTool.php` (`search-agents-tool`): a shell tool on Keystone's `Mcp\Tool`. `schema()` has a described, required `q`. `outputSchema()` is `data` alone, an array of `AgentReferenceResource::schema()` objects.
- `src/Domains/VirtualAgent/Mcp/Tools/ListRunnableAgentsTool.php` (`list-runnable-agents-tool`): an empty input schema; `outputSchema()` is `data` alone.
- `src/Domains/VirtualAgent/Mcp/Tools/ShowSettingsSummaryTool.php` (`show-settings-summary-tool`): an empty input schema; `outputSchema()` is `SettingsSummaryResource::structuredSchema()`.
- `src/Mcp/CortexServer.php`: the three tools are listed in `TOOLS`, in a new "Agent directory and settings" block after the virtual agent version tools.

### Tests

- `tests/Feature/Mcp/AgentDirectoryToolsTest.php` (every call goes through `mcpTool()`, which also checks the answer against the output schema) covers:
  - the three tools are in `CortexServer::TOOLS`, with the names `search-agents-tool`, `list-runnable-agents-tool` and `show-settings-summary-tool`
  - parity of `search-agents-tool` with `cortex.search.index`, for results and for `data: []`
  - search refused without `q` and with `q` over 255 characters
  - parity of `list-runnable-agents-tool` with `cortex.runnable-agents.index`: the full list, `data: []` when nothing is runnable, and per-kind `run` filtering for a signed-in user
  - `Unauthorized.` from search and runnable agents when `viewAny` is denied on both models, for a guest and for a signed-in user
  - parity of `show-settings-summary-tool` with `cortex.settings-summary.show`, with providers configured and with none
- Not run here: the gate runs `vendor/bin/pest` after the build. `pint --test` passes on the changed files, and `phpstan analyse` passes on `src/Domains/VirtualAgent/Mcp` and `src/Mcp/CortexServer.php`.

### Deviations

- `tests/Feature/Mcp/OutputSchemaTest.php` is outside this group's file list. It pins the number of tools that answer with structured content at 27. The three new tools all do, so the count is now 30. The test's own assertion (every such tool declares `outputSchema()`) is unchanged.

### Retry fix

- The earlier commit already held every task of this group, so no group 4 code changed on retry.
- `tests/Feature/Mcp/ServerContextPinTest.php` failed in this worktree, and the failure is not caused by this group. `composer.lock` is not tracked, and `laravel/mcp: ^1.0.1` resolved to v1.2.1 here; the main checkout has v1.1.2. v1.2.1 adds an optional promoted `?HeaderBag $headers = null` as the last `ServerContext` constructor parameter, so the vendor pin (D-025, D-051) no longer matched. `ServerContextRebuilder` copies every promoted constructor parameter by name, so it already carries `headers` over. The rebuild tests in the same file pass on v1.2.1. The pin now accepts the nine required parameters, optionally followed by `headers`; every parameter must still be promoted, and only `headers` may be optional. It holds on v1.1.2 and v1.2.1. This file is outside the group's list.
- Checked here: `ServerContextPinTest`, `AgentDirectoryToolsTest` and `OutputSchemaTest` pass, and `pint --test` passes on the edited file. `tests/Feature/Mcp` and `tests/Unit` had only the pin failure before the fix. The full suite is left to the gate.

## Group 5

Remove the Atrium UI, AC 7 to 12.

### Files

- Deleted: `src/Atrium/`, `resources/views/`, `tests/Feature/Ui/`, `tests/WithoutAtrium/`, `tests/Modes/PennantPlus/`, `tests/PennantPlusTestCase.php`, `tests/Fixtures/views/screen-probe.blade.php`, `tests/Feature/HistoryNavigationTest.php`, `tests/Feature/NavigationSectionTest.php`, `tests/Feature/NoHistoryUiTest.php`, `phpunit.without-atrium.xml`, `workbench/app/Http/Controllers/TestHostScreenController.php`, `workbench/resources/views/test-host.blade.php`.
- `src/CortexServiceProvider.php`: drops `registerAtriumPlugin()`, `registerAtriumScreens()`, `loadViewsFrom()`, `Blade::if('cortexCan')` and the `cortex-views` publish. It keeps `loadTranslationsFrom()` and the `cortex-lang` publish (D-012).
- `src/Domains/RedirectDomain/Policies/RedirectDomainPolicy.php`, `src/Domains/ConcreteServer/Policies/McpInstructionPolicy.php`: the docblocks no longer name Atrium screens or the tool editor.
- `config/cortex.php`: the `ui` and `atrium` sections and the `CortexSupportFeature` import are removed. The Auditing banner no longer names Keen (D-011). The OAuth redirect domains and tool tags banners no longer mention the dashboard.
- `lang/en/cortex.php`: only `redirect_domain_invalid` is left.
- `composer.json`: Atrium and PennantPlus are removed from `require-dev`, `suggest`, `conflict` (now empty, so the key is gone) and `repositories`. `extra.atrium` and the `test:without-atrium` script (and its `test` entry) are removed too. Keen stays in `require-dev`.
- `testbench.yaml`: drops the Atrium provider, `start: /atrium`, `atrium-assets` and the Pennant migrations path. That path only served PennantPlus's features table and named it in a comment.
- `phpunit.xml.dist`: the WithoutAtrium comment is removed. `.github/workflows/tests.yml`: the `without-atrium` job is removed.
- `workbench/app/Providers/WorkbenchServiceProvider.php`: drops the `viewAtrium` gate and the PennantPlus `pennant.*` store config.
- `tests/TestCase.php`: drops `AtriumServiceProvider`, the test-host route and the `workbench` view namespace. `tests/Pest.php` drops `ResetsScreenExtensions`, the PennantPlus and WithoutAtrium bindings.

### Tests

- `tests/Feature/NoAtriumTest.php` (new) covers:
  - no `atrium` or `pennantplus` text (case-insensitive) in `src/`, `config/`, `lang/`, `workbench/` (minus its git-ignored `storage/` and `.env*`), `.github/workflows/`, `composer.json`, `testbench.yaml` or `phpunit.xml.dist`
  - each deleted path of AC 8 is absent
  - no route named `atrium.*`, while `cortex.search.index` is registered
  - `config('cortex.ui')` and `config('cortex.atrium')` are null
  - no `cortexCan` or `endcortexCan` Blade directive
  - `cortex-views` is not a publish group, while `cortex-config` and `cortex-lang` are
  - every `lang/en/cortex.php` key is referenced from `src/` as `cortex::cortex.{key}`
- `tests/Feature/ComposerTest.php` (rewritten for AC 11) covers:
  - Keystone is the only `refactor-circus/*` package in `require`
  - neither Atrium nor PennantPlus is in `require`, `require-dev`, `suggest`, `conflict`, `replace` or `provide`
  - no Atrium or PennantPlus repository
  - no `extra.atrium`, and neither name anywhere in the manifest
  - no `test:without-atrium` script, and `test` keeps `@test:unit` but no `@test:without-atrium`
  - no `without-atrium` text in the CI workflow
  - no Roster
- `tests/ArchTest.php`: `RefactorCircus\Cortex` uses no `RefactorCircus\Atrium`, `RefactorCircus\PennantPlus` or `Dedoc`. This replaces the old "only the Atrium layer uses Atrium" rule.
- Mixed files, keeping only the cases that do not need Atrium:
  - `LockedToolsTest`: the dashboard case is now an API-only check of `data.tools_overridable`.
  - `PerformanceTest`: the redirect-domain UI paging, dashboard virtual-agent list and `CortexPlugin` search cases are removed. One-query override lookup for search is covered by `SearchAgentsActionsTest` (Group 1).
  - `RedirectDomainsTest`: the dashboard case is removed. The JSON API owner-filter case (AC 7) stays unchanged.
  - `ToolTagsTest`: the `dashboard` describe block is removed.
  - `McpRegistrationTest`, `ServerToolOverrideTest`: the `ScreenExtensions::flush()` resets are removed.
  - `ExampleTest`: the `cortex.ui.enabled` and views assertions are removed, and the translation check uses `redirect_domain_invalid`.
  - `DocsTest`: the README "Redirect domains on other screens" checks, the Atrium conflict helper and checks, the "no longer says Cortex requires Atrium" check and the v0.1.0 Atrium upgrade-note checks are removed. Group 7 adds the v2 docs checks. `markdownSection()` is kept for them.
  - `NoRosterReferenceTest`: the comment no longer names `cortex.atrium.*`.
- Checked here: `composer analyse` (PHPStan, whole package) and `composer lint:check` (Pint) pass, and `php -l` passes on the new test files. `vendor/bin/pest` is left to the gate, as instructed.

### Deviations

- Two files outside the group's list were touched:
  - `tests/Fixtures/OrphanedSupportFeature.php` was deleted. It was the fixture for the removed `CortexSupportFeature` and was used only by the deleted `tests/Feature/Ui` tests.
  - `tests/Feature/ToolOnlyRowTest.php`: its docblock no longer says the "Atrium screen is covered with the editor", since that test is gone.
- `composer.lock` and `vendor/` are not tracked and were not updated, so Atrium and PennantPlus are still installed in this worktree's `vendor/`. Nothing registers or references them: `TestCase` lists its providers explicitly, and the arch and text tests prove the source names neither. `composer validate` warns that the lock is out of date. A `composer update` (or `composer local:update`) drops them.
- `testbench.yaml` has no `workbench.start` now, so `composer serve` opens `/`. Group 6 can point it at the Scramble docs page if it wants.

### Retry check

- The earlier commit already held every task of this group, so no code changed on retry.
- A search of `src/`, `config/`, `lang/`, `workbench/`, `tests/`, `composer.json`, `testbench.yaml`, `phpunit.xml.dist` and `.github/` found no leftover `ui.enabled`, `cortex::ui`, `SupportFeature`, `ScreenExtensions`, `cortex-views`, `viewAtrium`, `test-host` or `cortexCan`. The only files naming Atrium are the three tests that assert its absence and `.github/CONTRIBUTING.md`, which Group 7 updates.
- Re-checked here: every test file this group touched passes (`NoAtriumTest`, `ComposerTest`, `ArchTest`, `DocsTest`, `ExampleTest`, `LockedToolsTest`, `PerformanceTest`, `RedirectDomainsTest`, `ToolTagsTest`, `NoRosterReferenceTest`, `McpRegistrationTest`, `ServerToolOverrideTest`, `ToolOnlyRowTest`), and so does `tests/Feature/Compat`: 186 tests in all. `composer analyse` also passes. The full suite is left to the gate.
