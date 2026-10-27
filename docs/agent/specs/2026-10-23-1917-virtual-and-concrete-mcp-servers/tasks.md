# Tasks: Concrete MCP servers: rename and tool override

## Group 1: Rename McpServer domain to ConcreteServer
depends on: none
files: src/Domains/McpServer/**, src/Domains/ConcreteServer/**, src/Domains/DomainServiceProvider.php, src/Cortex.php, src/Facades/Cortex.php, src/Mcp/CortexServer.php, src/Atrium/CortexPlugin.php, src/Atrium/ScreenAccess.php, src/Atrium/Http/Controllers/McpInstructionUiController.php, src/Atrium/Http/Controllers/ServerUiController.php, resources/views/ui/servers/instructions.blade.php, config/cortex.php, database/factories/McpInstructionFactory.php, database/factories/McpInstructionVersionFactory.php, workbench/app/Mcp/Servers/CatalogServer.php, workbench/app/Mcp/Servers/SupportServer.php, workbench/database/seeders/DatabaseSeeder.php, phpunit.xml.dist, phpunit.xml, composer.json, ../Keystone/src/Cortex/CortexIntegration.php, ../Keystone/tests/**, tests/Feature/Http/McpInstructionsTest.php, tests/Feature/ModelsTest.php, tests/Feature/McpServerRegistryTest.php, tests/Feature/PerformanceTest.php, tests/Feature/EventsTest.php, tests/Feature/PublicationCacheTest.php, tests/Feature/PolicyTest.php, tests/Feature/ServerBaseTest.php, tests/Feature/Mcp/ServerInstructionToolsTest.php, tests/Feature/Ui/ScreenControlsTest.php, tests/Modes/RenamedTables/RenamedTablesTest.php, tests/Fixtures/EchoServer.php, tests/Fixtures/PlainServer.php, tests/Fixtures/Policies/SignedInMcpInstructionPolicy.php, tests/Fixtures/Policies/GrantedMcpInstructionPolicy.php, AGENTS.md
criteria: 1, 2

- [x] 1.0a Pin the test env (D-053): add `<env name="SESSION_DRIVER" value="array" force="true"/>`, `CACHE_STORE=array` and `APP_ENV=testing` (all `force="true"`) to `phpunit.xml.dist` (and `phpunit.xml` if present)
- [x] 1.0b Keystone follows the rename (D-052): in the sibling `../Keystone` checkout, on a new branch in its own repo, change `Cortex\CortexIntegration` to resolve `RefactorCircus\Cortex\Domains\ConcreteServer\Services\{McpServerRegistry,McpInstructionOverrides}` and fall back to the old `Domains\McpServer\Services\...` names through `class_exists()`; add a Keystone test for both lookups and run its suite; commit there (no push, no release); build Cortex against it with `composer local:install`. Releasing Keystone and raising Cortex's `refactor-circus/keystone` constraint is a pre-merge step listed in the PR body
- [x] 1.1 Move the existing server tests' imports to the new namespace (assertions unchanged, imports only) and add a test that `ConcreteServerServiceProvider` is registered by `DomainServiceProvider` and `src/Domains/McpServer` no longer exists
- [x] 1.2 `git mv` the folder to `src/Domains/ConcreteServer`, rename the provider to `ConcreteServerServiceProvider`, re-namespace every class and update every reference in src, config, factories, workbench and views; class stems (`McpInstruction*`, `McpServer*`), table keys, route URIs/names, MCP tool names, config keys and server names stay unchanged
- [x] 1.3 Run composer test:unit; all green

## Group 2: Class-name shims
depends on: 1
files: src/Domains/ConcreteServer/shims.php, composer.json, tests/Fixtures/old-namespace-child.php, tests/Fixtures/OldNamespaceServer.php, tests/Fixtures/OldTraitServer.php, tests/Feature/Compat/ClassShimsTest.php
criteria: 6, 7

- [x] 2.1 Write tests: old `Support\Server` and old trait host servers still apply the instructions override (the tool-override half of AC 6 is group 5's `OldNamespaceToolOverrideTest`); `class_exists()` on each old name (`Support\Server`, `Concerns\HasVersionedInstructions`, the two models, the two policies) is true and resolves to the new class (`ReflectionClass::getName()`); subprocess test with the checked-in `old-namespace-child.php` fixture run with `PHP_BINARY` (precondition first: `vendor/composer/autoload_files.php` lists the shim, else a message to run `composer dump-autoload`; lazy check `class_exists($old, false)` false for each old name before declaring; declares a class extending each old FQCN, prints a marker, exit 0; an unshimmed old name requested with no booted app does not throw); unshimmed old name with a booted app logs the `<old> moved to <new>` warning, defines no alias and `class_exists()` stays false; `composer.json` lists the shim in `autoload.files` and the shim carries `@deprecated`
- [x] 2.2 Implement the lazy shim file (`spl_autoload_register` fallback for the `Domains\McpServer\` prefix; aliases for `Support\Server`, `Concerns\HasVersionedInstructions`, the two models and two policies; guarded against recursion and re-aliasing, `class_exists($new, true)` at most once per name, never throws; "moved to" warning for other old names, safe with no booted app) and add it back to `composer.json` `autoload.files`; run `composer dump-autoload`
- [x] 2.3 Run composer test:unit; all green (D-054 failure accepted)

## Group 10: Morph aliases
depends on: 2
files: src/Domains/ConcreteServer/ConcreteServerServiceProvider.php, tests/Feature/Compat/MorphAliasesTest.php
criteria: 3, 4, 5

- [x] 10.1 Write tests: morph map both directions for both models; a stored polymorphic row holding the old class string resolves to the new model; a host `Relation::morphMap(['other' => …])` after boot keeps both Cortex keys; booted-callback warning naming the key when an old key no longer resolves (once per process) and none when it does, plus the warning when `getMorphClass()` of a new model differs from the old string; new saves write the old string; Keen variants (stored audit entry with the old string resolves; audit row from a new save carries the old string) with `->skip(! class_exists(...))`; Gate: a row loaded from an old morph string through the relation gets the new policy from `Gate::getPolicyFor()` and a denying policy registered for the new class refuses `authorize`
- [x] 10.2 Implement `keepMorphAliases()` through the merging `Relation::morphMap($map)` (never `$merge = false`, never `enforceMorphMap()`) and the non-throwing booted morph check
- [x] 10.3 Run composer test:unit; all green (D-054 failure accepted)

## Group 11: Policy-key remap
depends on: 2
files: src/Domains/ConcreteServer/ConcreteServerServiceProvider.php, src/CortexServiceProvider.php, tests/Fixtures/Policies/DenyingOldKeyMcpInstructionPolicy.php, tests/Feature/Compat/OldPolicyKeysTest.php
criteria: 7

- [x] 11.1 Write tests: a host `cortex.policies` entry keyed by an old model FQCN with a denying policy refuses the guarded action over HTTP and MCP (no fallback to the bundled allow-all policy); one deprecation warning naming the old and new keys per remapped key; with both old and new keys present the new key wins and the old one is still warned about; an old policy FQCN as the value resolves through the shims
- [x] 11.2 Implement the `cortex.policies` old-key remap so it runs before `registerPolicies()`
- [x] 11.3 Run composer test:unit; all green (D-054 failure accepted)

## Group 12: Keen and CI checks
depends on: 2, 10, 11
files: composer.json, composer.lock, .github/workflows/tests.yml, tests/Feature/Compat/KeenInCiTest.php, tests/Feature/Compat/OldNamespaceReferencesTest.php
criteria: 2, 3, 6

- [x] 12.1 Write tests: CI guard fails when the `CI` environment variable is set and the Keen audit model class is missing (passes or skips locally otherwise); the `Domains\McpServer` string scan over `src/` and `tests/` with an exact named allowlist (the shim file, the morph-alias, old-row, old-policy-key and subprocess fixture files from groups 2, 10 and 11; members and count asserted)
- [x] 12.2 Confirm `refactor-circus/keen` stays in `require-dev` with the lock entry (done in 9.1) and `@composer dump-autoload` stays first in the `composer test` script; add the `composer dump-autoload` step after install and before the tests in the CI workflow
- [x] 12.3 Run composer test:unit; all green (D-054 failure accepted)

## Group 3: Tools column, registry and tool-override storage
depends on: 12
files: database/migrations/2026_10_23_000002_add_tools_to_cortex_mcp_instructions.php, src/Domains/ConcreteServer/Models/McpInstructionModel.php, src/Domains/ConcreteServer/Services/McpServerRegistry.php, src/Domains/ConcreteServer/Services/McpToolOverrides.php, src/Domains/ConcreteServer/Services/ToolsColumn.php, src/Support/PublicationCache.php, src/Support/WarningLimiter.php, config/cortex.php, src/Domains/ConcreteServer/ConcreteServerServiceProvider.php, tests/Unit/McpServerRegistryDefaultToolsTest.php, tests/Feature/ToolsMigrationTest.php, tests/Feature/McpToolOverridesTest.php, tests/Feature/WarningLimiterTest.php, tests/Fixtures/GroupedToolsServer.php
criteria: 8, 12, 16, 18, 22

- [x] 3.1 Write tests: `defaultTools()` flattens `ToolSearch` groups and uses `name()`, `supportsToolOverrides()` false for `cortex` only (a `CortexServer` subclass under another name is overridable); staged migration test (glob, migrate to the step before, insert, migrate, `tools` null, roll back drops the column and keeps the row), running the migration twice is a no-op; `McpToolOverrides` maps every row with non-null `tools` (published or not), reads a non-array value as null with the `server`/`value_type` warning, cached under `mcpToolsKey()`; the missing-column check uses the model's connection (non-default connection test) and is memoized per process; the limiter claims with `Cache::add`, honours `cortex.mcp.warning_ttl` (int cast, hard default 3600, clamped to ≥1, time travel past the configured value), bounds and prefixes keys, limits entries independently, and falls back to logging with a real throwing `Store` driver; `beforeEach` flushes the cache
- [x] 3.2 Implement the migration (guards via `Schema::hasColumn()` with the table from `Support\Tables`), the model cast and `$fillable`, the registry methods, `McpToolOverrides`, the column check, the limiter and the config section with its banner
- [x] 3.3 Run composer test:unit; all green (D-054 failure accepted)
- [x] 3.4 Write tests (D-055): the schema check throws → `McpToolOverrides::for()` reports no tool override for that call, a warning is logged once for two calls within the TTL (context: server name and exception message, limiter subject `column-check:<exception class>`), nothing is memoized, and the next call checks again and sees the column once the check succeeds
- [x] 3.5 Implement D-055 in `ToolsColumn` / `McpToolOverrides`: catch the throwing check, return "no override" without memoizing, warn through `WarningLimiter`
- [x] 3.6 Run composer test:unit; all green (D-054 failure accepted)

## Group 4: Update, clear and delete actions with policy and boot warning
depends on: 3
files: src/Domains/ConcreteServer/Actions/UpdateConcreteServerToolsAction.php, src/Domains/ConcreteServer/Actions/ClearConcreteServerToolsAction.php, src/Domains/ConcreteServer/Actions/DeleteMcpInstructionAction.php, src/Domains/ConcreteServer/Data/UpdateConcreteServerToolsData.php, src/Domains/ConcreteServer/Data/ClearConcreteServerToolsData.php, src/Domains/ConcreteServer/Events/ConcreteServerToolsUpdatingActionEvent.php, src/Domains/ConcreteServer/Events/ConcreteServerToolsUpdatedActionEvent.php, src/Domains/ConcreteServer/Events/ConcreteServerToolsClearingActionEvent.php, src/Domains/ConcreteServer/Events/ConcreteServerToolsClearedActionEvent.php, src/Domains/ConcreteServer/Policies/McpInstructionPolicy.php, src/Domains/ConcreteServer/Services/GuestToolsWarning.php, src/Domains/ConcreteServer/ConcreteServerServiceProvider.php, config/cortex.php, tests/Feature/ConcreteServerToolsActionsTest.php, tests/Feature/ConcreteServerToolsRaceTest.php, tests/Feature/ConcreteServerToolsCacheTest.php, tests/Feature/DeleteMcpInstructionActionTest.php, tests/Feature/GuestToolsWarningTest.php, tests/Fixtures/Policies/NoUpdateToolsMcpInstructionPolicy.php, tests/Fixtures/Policies/PartialUpdateToolsMcpInstructionPolicy.php
criteria: 9, 9a, 11, 12, 13, 17

- [x] 4.1 Write tests: update rules (`present|nullable|array`, distinct, code or registered name, 422 on `tools.N`, duplicate served `name()` 422 on the later entry, empty list stored as `[]`, `cortex` 422), create-or-update from a `lockForUpdate()` re-read, unique-collision race via a `creating` listener (`DatabaseMigrations`, file SQLite), `updateTools` ability and host policy without `updateTools` denies; clear sets `tools` null, keeps instructions/versions, removes a tool-only row, keeps a row when a version is created by a starting-event listener (flag asserted), no-op without override; delete keeps a row with non-null `tools`, deletes versions one model at a time (events, Keen audit when installed), race with a starting-event query-builder write (flag asserted), rollback when the second version delete throws; cache (a)/(b)/(c) under `DatabaseMigrations` with a `forget` spy recording `DB::transactionLevel()`, failed after-commit forget logged and action succeeds; guest boot warning (booted callback on fresh non-local web boot, bundled policy names all overridable servers, none in `local`, console, denying policy, throwing policy; partial-allow logs `servers` = `[A]`; second call logs nothing; static reset in `beforeEach`)
- [x] 4.2 Implement the Data, both actions with their events and transactions, the delete action change, the `updateTools` policy ability with its guest docblock, after-commit forgets wrapped against a throwing store, and `GuestToolsWarning` called from the provider's `booted` callback
- [x] 4.3 Run composer test:unit; all green (D-054 failure accepted)

## Group 5: Serving the overridden tool list
depends on: 3
files: src/Domains/ConcreteServer/Concerns/HasCortexOverrides.php, src/Domains/ConcreteServer/Concerns/HasVersionedInstructions.php, src/Domains/ConcreteServer/Support/Server.php, src/Domains/ConcreteServer/Services/ServerToolResolver.php, src/Domains/ConcreteServer/Services/ServerContextRebuilder.php, src/Domains/ConcreteServer/Services/ServerContextMemo.php, tests/Support/ServerContextTools.php, tests/Feature/Mcp/ServerToolOverrideTest.php, tests/Feature/Mcp/ServerToolOverrideWarningsTest.php, tests/Feature/Mcp/ServerContextPinTest.php, tests/Feature/Compat/OldNamespaceToolOverrideTest.php, tests/Fixtures/ConstructorToolsServer.php, tests/Fixtures/HiddenMcpTool.php, tests/Fixtures/ThrowingCortexTool.php, tests/Fixtures/GatedAiTool.php, tests/Fixtures/CollidingCortexTool.php
criteria: 6, 8, 10, 10a, 10b, 10c, 10d, 10e, 10f, 12, 16, 18

- [x] 5.1 Write tests with the Laravel MCP test client: override lists exactly the stored tools, null/no row lists code tools, removed tool's `tools/call` fails naming the tool, tool-only row served; vendor pin on `ServerContext` constructor and `$tools` plus `tools/list` order preservation, then stored order asserted on the context (shared reflection helper) and on `tools/list`; `ToolSearch` server served flat; constructor/`boot()` tools selectable; ambiguity (code wins, one warning per entry per request, memo across two `createContext()` calls, request-scoped memo sees a new list on the next request); `shouldRegister()` false stays hidden, `AiToolAsMcpTool` gating hidden then served with the request user reaching it; stale and throwing entries skipped with the `server`/`entry`(/message) warning, rate-limited across requests; corrupt value serves code tools; missing column serves code tools with instructions applied and logs once; rebuild failure logs (limited by server + exception class) and serves code tools; `cortex` row with `tools` ignored on its registered route; old-namespace host servers serve a saved tool override
- [x] 5.2 Implement `HasCortexOverrides` (old trait delegating), the resolver (code tools from the parent context by `name()`, registered tools from `ToolRegistry`, `Laravel\Ai` through `AiToolAsMcpTool`), the context rebuild with its fallback, and the request-scoped memo
- [x] 5.3 Run composer test:unit; all green (D-054 failure accepted)

## Group 6: HTTP and MCP surfaces and resource payload
depends on: 4, 5
files: src/Domains/ConcreteServer/routes.php, src/Domains/ConcreteServer/Http/Controllers/McpServerToolsController.php, src/Domains/ConcreteServer/Http/Requests/UpdateConcreteServerToolsRequest.php, src/Domains/ConcreteServer/Http/Requests/ClearConcreteServerToolsRequest.php, src/Domains/ConcreteServer/Mcp/Requests/UpdateServerToolsMcpRequest.php, src/Domains/ConcreteServer/Mcp/Requests/ClearServerToolsMcpRequest.php, src/Domains/ConcreteServer/Mcp/Tools/UpdateServerToolsTool.php, src/Domains/ConcreteServer/Mcp/Tools/ClearServerToolsTool.php, src/Mcp/CortexServer.php, src/Domains/ConcreteServer/Http/Resources/McpServerResource.php, src/Domains/ConcreteServer/Resources/McpInstructionResource.php, tests/Feature/Http/ConcreteServerToolsTest.php, tests/Feature/Mcp/ServerToolsToolsTest.php, tests/Feature/ToolOnlyRowTest.php
criteria: 9, 11, 12, 13, 14, 15

- [x] 6.1 Write tests: `PUT`/`DELETE cortex/servers/{server}/tools` by route name and `update-server-tools`/`clear-server-tools` (422/404 mapping, `Not found.`, authorization 401/403, denying policy keeps the stored list), parity of stored state and payload with `Testing\Parity::gaps()` empty; resource `tools`, `tools_overridden`, `missing_tools`, `ambiguous_tools` for no override, valid, stale/ambiguous and throwing entries, server with no row and `cortex`; DELETE instructions on an instructions+tools row answers with `tools_overridden` true; tool-only row on the server list, the instructions show, the version list and publishing
  - The DELETE-instructions response, settled by D-057, was finished in 6.4–6.6: the test now asserts 200 with `tools_overridden` true.
- [x] 6.2 Implement the routes, controller, HTTP and MCP requests (`fromValidated`), tools with `outputSchema()`, `CortexServer::TOOLS` entries, and the four resource fields with `schema()`
- [x] 6.3 Run composer test:unit; all green (D-054 failure accepted)
- [x] 6.4 Write tests (D-057): DELETE `cortex/servers/{server}/instructions` on an instructions-only row answers 204 and `delete-server-instructions` answers text (existing assertions unchanged); on an instructions+tools row the route answers 200 with the `McpInstructionResource` payload (`tools_overridden` true) and the tool answers the same resource as structured content, checked against its `outputSchema()` through `mcpTool()`; finish 6.1's DELETE-instructions assertions with these
- [x] 6.5 Implement D-057 in `DeleteMcpInstructionRequest` and `DeleteServerInstructionsMcpRequest` (the delete action reports whether the row survived): 204/text when the row is removed, 200 `McpInstructionResource` / `toStructuredResponse()` when it survives; declare the tool's `outputSchema()` for the structured branch
- [x] 6.6 Run composer test:unit; all green (D-054 failure accepted)

## Group 7: Atrium tool override editor
depends on: 6
files: src/Atrium/Http/Controllers/McpInstructionUiController.php, src/Atrium/Http/Controllers/ServerUiController.php, src/Atrium/ScreenAccess.php, src/Atrium/routes.php, resources/views/ui/servers/instructions.blade.php, resources/views/ui/servers/partials/tools.blade.php, tests/Feature/Ui/ServerToolsEditorTest.php
criteria: 9, 12, 15, 19

- [x] 7.1 Write tests: checklist order (code tools, then registry tools; ordered `tools[]` values), checked state, `(unresolved)` label for stale entries; save stores the list, `restore` clears it; empty selection refused without `confirm_empty` and stores `[]` with it; order test (`['b','a']` + tick a, b, c → `['b','a','c']`; tick b, c → `['b','c']`); denying policy blocks a tool addition; `cortex` renders no `tools[]` and no `restore`; tool-only row renders with code-derived instructions; `StylesTest` passes
- [x] 7.2 Implement the editor partial with `x-atrium::*` components only, the save and restore handlers (`XData::from($request->all(), …)`) and the screen access checks
- [x] 7.3 Run composer test:unit; all green (D-054 failure accepted)

## Group 8: Renamed tables, docs and quality gate
depends on: 7
files: tests/Modes/RenamedTables/RenamedTablesTest.php, tests/Feature/DocsTest.php, README.md, CHANGELOG.md, config/cortex.php, resources/boost/skills/cortex-development/SKILL.md, docs/agent/standards/cortex/domains.md
criteria: 20, 21, 22

- [x] 8.1 Write tests: RenamedTables re-runs save, serve and restore of a tool override; docs test greps the README (routes, tools, `updateTools`, deny-by-default snippet with `return false`), the Boost skill (`update-server-tools`, `clear-server-tools`), the CHANGELOG Unreleased section (`dump-autoload -o`, `vendor:publish`, `migrate`, `updateTools`, `restart`), `config/cortex.php` (`warning_ttl` and its banner, `updateTools` in the policies comment) and `domains.md` (`ConcreteServer`)
- [x] 8.2 Write the README section on overriding a concrete server's tools (semantics, order promise, dynamic tools in validation, per-node cache, morph map keys, at-least-once delete events, lock unverified off SQLite, revocation delay, per-model version deletes, guest and privilege warnings, migration copy note, DELETE instructions behaviour, unshimmed-class break, rollback drops tools, management tools not registered), the CHANGELOG Unreleased entries (security-relevant upgrade note at the top), the Boost skill and `domains.md`
- [x] 8.4 (D-056) README and CHANGELOG say an instructions delete writes one Keen `mcp_instruction.deleted` audit entry; the per-version deletes show only as model events
- [x] 8.3 Run composer test (lint, analyse, Pest including ArchTest); all green, no new ArchTest exclusions (D-054 failure accepted)

## Group 9: Rework 1: Build against local refactor-circus packages
depends on: 1
files: composer.json, composer.lock, ring-master.yml
criteria: 2, 3, 4, 5, 6, 7, 22

- [x] 9.1 User note (answered in RingMaster): "run what you need and install all refactor-circus packages locally". Set the worktree up with `composer local:install` / `composer local:update` (every `refactor-circus/*` symlinked to its sibling checkout: Atrium, Keen, Keystone `b8e3477`, PennantPlus), never a plain `composer install`, and carry on from group 2. Done before relaunch (2026-10-24): leftover `composer.json` edits kept (`refactor-circus/keen: dev-main` in `require-dev`, the Keen VCS repository, `@composer dump-autoload` in `test`); `composer local:update refactor-circus/keen` symlinked `../Keen`; `composer update refactor-circus/keen --no-install` added Keen (`9da4b1f`) to the committed `composer.lock` (now task 12.2). The `src/Domains/ConcreteServer/shims.php` `autoload.files` entry was taken out because the file does not exist yet and broke autoloading; task 2.2 adds it back together with the file. Baseline `composer test:unit`: 378/379, the one failure being the known D-054 ArchTest security preset on symlinked Atrium. `ring-master.yml` `build.worktree.setup` is now `composer local:install`, so later worktree setups keep the symlinks

## Group 13: Rework 2: Split group 2 and build groups 2–8
depends on: 1
files: docs/agent/specs/2026-10-23-1917-virtual-and-concrete-mcp-servers/tasks.md
criteria: 2, 3, 4, 5, 6, 7

- [x] 13.1 User note (answered in RingMaster): "Split group 2 into shims, morph aliases, policy-key remap, Keen/CI checks; build groups 2–8." Done before relaunch (2026-10-24): the old group 2 is now group 2 (class-name shims), group 10 (morph aliases), group 11 (policy-key remap) and group 12 (Keen and CI checks, including the `Domains\McpServer` allowlist scan, which needs the fixtures of the other three); group 3 now depends on group 12. The factory builds groups 2, 10, 11, 12, then 3–8, building against the local siblings (`composer local:install`, group 9)
