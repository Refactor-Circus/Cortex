# Build

## Group 1

Rename McpServer domain to ConcreteServer. Cortex commit `d1edd5c` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`; Keystone commit `b8e3477` on `ring-master/2026-10-23-1917-cortex-concrete-server-lookup` in `../Keystone` (not pushed, not released).

### Files

- `src/Domains/McpServer/**` moved with `git mv` to `src/Domains/ConcreteServer/**`, every class re-namespaced to `RefactorCircus\Cortex\Domains\ConcreteServer\…`. `McpServerServiceProvider` is now `ConcreteServerServiceProvider`. Class stems (`McpInstruction*`, `McpServer*`), table keys, route URIs and names, MCP tool names, config keys and server names are unchanged.
- References updated (namespace imports only): `src/Domains/DomainServiceProvider.php`, `src/Cortex.php`, `src/Facades/Cortex.php`, `src/Mcp/CortexServer.php`, `src/Atrium/CortexPlugin.php`, `src/Atrium/ScreenAccess.php`, `src/Atrium/Http/Controllers/{McpInstructionUiController,ServerUiController}.php`, `resources/views/ui/servers/instructions.blade.php`, `config/cortex.php`, `database/factories/{McpInstructionFactory,McpInstructionVersionFactory}.php`, `workbench/app/Mcp/Servers/{CatalogServer,SupportServer}.php`, `workbench/database/seeders/DatabaseSeeder.php`, `AGENTS.md`.
- `phpunit.xml.dist`: `<php>` block forcing `APP_ENV=testing`, `CACHE_STORE=array`, `SESSION_DRIVER=array` (D-053). There is no `phpunit.xml`.
- Keystone (D-052): `src/Cortex/CortexIntegration.php` resolves `McpServerRegistry` and `McpInstructionOverrides` through a private `serverService()` lookup. It returns the `Domains\ConcreteServer\Services\…` class, or the old `Domains\McpServer\Services\…` class when only that one exists (`class_exists()`). New `tests/Unit/CortexIntegrationTest.php` with stub services in `tests/Fixtures/cortex-services.php`, since Keystone cannot install Cortex.
- Cortex is built against that Keystone with `composer local:install` (`vendor/refactor-circus/keystone` is symlinked to `../Keystone`, locked at `b8e3477`).

### Tests

- Existing server tests moved to the new namespaces, with imports changed only and every assertion unchanged: `tests/Feature/{EventsTest,ModelsTest,McpServerRegistryTest,PerformanceTest,PolicyTest,PublicationCacheTest,ServerBaseTest}.php`, `tests/Feature/Http/McpInstructionsTest.php`, `tests/Feature/Mcp/ServerInstructionToolsTest.php`, `tests/Feature/Ui/ScreenControlsTest.php`, `tests/Modes/RenamedTables/RenamedTablesTest.php`, `tests/Fixtures/{EchoServer,PlainServer}.php`, `tests/Fixtures/Policies/{SignedIn,Granted}McpInstructionPolicy.php`.
- New (AC 1): `McpServerRegistryTest` "registers the ConcreteServer domain from the domain provider" checks that `DomainServiceProvider` lists `ConcreteServerServiceProvider`, that the provider is loaded, that `src/Domains/ConcreteServer` exists and that `src/Domains/McpServer` does not.
- Keystone: `composer test` passes (phpstan, pint, 100% type coverage, 82 tests).
- Cortex: `composer lint:check` and `composer analyse` pass. `composer test:unit`: 378 of 379 pass. The one failure, `ArchTest` "preset → security", is the environment and not this change: `local:install` symlinks `../Atrium`, so Pest's security preset counts Atrium's source as package code and flags `unserialize` in `Atrium/src/Domains/Search/Services/SearchRegistry.php:270`. The main checkout, which also symlinks Atrium, fails the same test on `main` before this job. CI installs Atrium from VCS into `vendor/`, where the preset does not scan it. Task 1.3 stays unticked until this is settled (see the open decision).

### Deviations

- The `Domains\McpServer` strings left in `README.md`, `docs/policies.md`, `resources/boost/skills/cortex-development/SKILL.md` and `CHANGELOG.md` (historical entries) are not changed. They are docs that group 1 does not own, and the shims of group 2 keep the README's `Support\Server` and trait references working. Group 8 owns the README, the Boost skill and the CHANGELOG. `docs/policies.md` has no owner and still names the old policy FQCNs.
- Pre-merge step for the PR body (D-052): release Keystone with commit `b8e3477` and raise Cortex's `refactor-circus/keystone` constraint to that release.

### Task 1.3

- No code changes, so there is no new commit; group 1's code is still `d1edd5c`.
- The worktree's `vendor/` had been reinstalled from VCS, which replaced the `../Keystone` symlink with Keystone `dff82dd` (from before D-052). On that install, 54 tests failed with "Target class [RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry] does not exist". Running `composer local:install` again put the symlinks back (Keystone `b8e3477`, Atrium, PennantPlus). Any later group must build against that Keystone the same way until it is released.
- `composer test:unit`: 378 of 379 pass. The one failure is ArchTest "preset → security", which flags `unserialize` in the symlinked Atrium's `SearchRegistry.php:270`. Per D-054 this is a known local-environment failure. Task 1.3 is ticked.

## Group 2

Class-name shims (AC 6, 7). Cortex commit `7242415` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`, built against the local siblings (`composer local:install`, Keystone `b8e3477`).

### Files

- `src/Domains/ConcreteServer/shims.php` (new). The lazy shim (D-027). When the file loads it registers one `spl_autoload_register` fallback for the `RefactorCircus\Cortex\Domains\McpServer\` prefix, appended after Composer's loader, and does nothing else. No class is aliased or loaded until an old name is requested.
  - Shimmed names: `Support\Server`, `Concerns\HasVersionedInstructions`, `Models\McpInstructionModel`, `Models\McpInstructionVersionModel`, `Policies\McpInstructionPolicy` and `Policies\McpInstructionVersionPolicy`. Each one gets `class_alias($new, $old)`, and each map entry has its own `@deprecated` docblock (removed in the next major).
  - Other old names (D-048): the fallback logs `"<old> moved to <new>. Cortex keeps no alias for it: …"` as a warning, with context `old` and `new`. It defines no alias, so the load still ends in class-not-found (D-043). It logs only when the new class exists.
  - Robustness: each old name is handled at most once per process. The name is marked before anything else runs, which guards against recursion and means `class_exists($new, true)` runs at most once per name. An old name that is already declared is never aliased again. The lookup ignores case, like PHP class names. The whole body is wrapped in `catch (Throwable)`, so it never throws.
  - With no booted app: the warning goes through `Illuminate\Container\Container::getInstance()->make('log')` only when the `Container` class is already loaded (`class_exists(..., false)`) and `log` is bound. Otherwise it is skipped silently. This was the spec's "whichever is chosen, it must not fail" point.
- `composer.json`: `autoload.files` lists `src/Domains/ConcreteServer/shims.php` again, and `composer dump-autoload` was run. This commit also carries the uncommitted group 9 edits to `composer.json`: `refactor-circus/keen` in `require-dev`, the Keen VCS repository, and `@composer dump-autoload` first in `test`.
- `tests/Fixtures/OldNamespaceServer.php` extends the old `Support\Server`. `tests/Fixtures/OldTraitServer.php` uses the old trait on `Laravel\Mcp\Server`. Both were already in the worktree, untracked, and are committed unchanged.
- `tests/Fixtures/old-namespace-child.php` (new, checked in). It requires `vendor/autoload.php` from the package root (`dirname(__DIR__, 2)`) and never boots Laravel. It then:
  - asserts `class_exists`/`trait_exists($old, false)` is false for all six old names (the lazy check);
  - requests the unshimmed `Exceptions\McpServerNotFoundException` and expects false and no exception;
  - declares classes that extend the old `Support\Server` and the two old policies, and one that uses the old trait;
  - asserts each one resolves to the new class or trait;
  - prints `old-namespace-child: ok`.
  Any failed check throws, so the exit code is non-zero.

### Tests

- `tests/Feature/Compat/ClassShimsTest.php` (12 tests):
  - Old `Support\Server` and old-trait host servers serve the published instructions override, and serve their code instructions when there is none.
  - All six old names exist and resolve to the new class (`ReflectionClass::getName()`), as a dataset.
  - With a booted app, an unshimmed old name (`Actions\DeleteMcpInstructionAction`) logs the `<old> moved to <new>` warning exactly once, even when requested twice. No alias is defined and `class_exists()` stays false.
  - Subprocess test: the precondition comes first and checks that `vendor/composer/autoload_files.php` lists the shim, failing with "run `composer dump-autoload` and rerun" otherwise. Then `PHP_BINARY` runs the child fixture, and the test asserts exit code 0 and the marker.
  - `composer.json` `autoload.files` holds the shim, and every shimmed old name in the shim file is preceded by a `@deprecated` docblock.
- Seen failing first: all 12 failed before `shims.php` existed.
- `composer test:unit`: 390 of 391 pass. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium. `composer analyse` passes, `pint --test` on the new files passes, and `composer test:types` is 100%.

### Deviations

- Trait existence check. PHP's `class_exists()` is false for a trait even after `class_alias`, so the test and the child check `Concerns\HasVersionedInstructions` with `trait_exists()`. The other five old names use `class_exists()`, as AC 6 states.
- Models in the child. `McpInstructionModel` and `McpInstructionVersionModel` are `final`, as they were before the rename, so no class can extend them. The child requests them by their old names (`class_exists()` plus `ReflectionClass::getName()`) and does not extend them. It does extend `Support\Server` and both policies, and it uses the trait.
- The untracked `tests/Fixtures/Policies/DenyingOldKeyMcpInstructionPolicy.php` belongs to group 11. It was left in the worktree uncommitted.

## Group 10

Morph aliases (AC 3, 4, 5). Cortex commit `13fb9a3` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`, built against the local siblings (`composer local:install`, Keystone `b8e3477`, Keen symlinked).

### Files

- `src/Domains/ConcreteServer/ConcreteServerServiceProvider.php`:
  - `boot()` calls Keystone's `keepMorphAliases()` with a private `MORPH_ALIASES` constant. It holds exactly the two entries of AC 3: each old `Domains\McpServer\Models\…` FQCN, as a string, maps to its new model class. Keystone's method is the merging `Relation::morphMap($map)`. Cortex never passes `$merge = false` and never calls `enforceMorphMap()`.
  - `boot()` also registers `$this->app->booted(fn () => $this->checkMorphAliases())`.
  - `checkMorphAliases()` (public, `@internal`) runs once per process, guarded by a static flag. For each key it logs one `Log::warning` in either of two cases. When `Relation::getMorphedModel($old)` no longer returns the new class, the message names the key, with context `key`, `model` and `resolved`. When it does resolve but `getMorphClass()` of the new model differs from the old string (a host re-mapped the class), the message names the key and the written alias, with context `key`, `model` and `morph_class`. Each key is checked inside `try`/`catch (Throwable)`, so the check never throws.
  - `resetMorphAliasCheck()` (public static, `@internal`) clears the flag for tests, like the D-037 pattern.
  - Group 12's `Domains\McpServer` allowlist must include this provider: it carries the two old FQCN strings, and group 11 adds the policy-key remap here as well.
- `tests/Feature/Compat/MorphAliasesTest.php` (new).
- `tests/Feature/ModelsTest.php` (outside the group's file list, see Deviations).

### Tests

- `tests/Feature/Compat/MorphAliasesTest.php` (14 tests). `beforeEach` snapshots `Relation::$morphMap` and resets the check flag. `afterEach` restores both, because the morph map is static and lasts for the whole process.
  - Both directions for both models, as a dataset: `getMorphedModel(old)` returns the new class, and `getMorphClass()` returns the old string.
  - The map's Cortex entries are exactly the two keys, and `Relation::requiresMorphMap()` is false.
  - A stored polymorphic row holding the old string resolves to the new model, for both models. The row is a `redirect_domains` row inserted with the query builder, and it is loaded through the `owner` morphTo.
  - A host `Relation::morphMap(['other' => User::class])` after boot keeps both Cortex keys in both directions.
  - New saves write the old string: `owner()->associate()` on both models, with the raw `owner_type` column read back.
  - Boot check:
    - logs nothing when both keys resolve;
    - with an unset key, logs exactly one warning naming the key (message and context `key`) across two calls;
    - never throws when `Log::warning` itself throws;
    - logs one warning naming the key and the host alias when a host re-maps `McpInstructionModel` under `host-instruction`;
    - runs from the `booted` callback. The app is already booted, so calling `boot()` again fires the callback at once and marks the process as checked. A broken map then logs nothing until the flag is reset.
  - Gate (AC 5): a row loaded from the old string through the relation gets `McpInstructionPolicy` from `Gate::getPolicyFor()`. After `Gate::policy()` registers a denying policy (declared in the test file) for the new class, `getPolicyFor()` returns it and `authorize('update', …)` throws `AuthorizationException`.
  - Keen variants, `->skip(! class_exists(AuditEntryModel::class))`. Both register `KeenServiceProvider` inside the running app and load Keen's migrations. A stored audit entry (raw insert) with the old `subject_type` resolves its `subject` to the new model. `PublishMcpInstructionVersionAction` writes an audit entry whose `subject_type` is the old string and whose subject resolves back. Both ran here, since Keen is installed.
- Seen failing first: all 14 failed (`resetMorphAliasCheck()` undefined) before the provider change.
- `composer test:unit`: 402 of 403 pass. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium. `composer lint:check`, `composer analyse` and `composer test:types` (100%) pass.

### Deviations

- `tests/Feature/ModelsTest.php` is not in group 10's file list. Its "stores models under their own class names" dataset listed `McpInstructionModel` and `McpInstructionVersionModel` and asserted that `getMorphClass()` returns the new class. AC 3 requires the old string, so those two rows failed once the aliases were registered. I removed the two rows and added a comment pointing to `MorphAliasesTest`, which asserts what they store now. The other six models keep their assertion. This is the one place where AC 2's "assertions unchanged" baseline yields to AC 3.
- The denying policy for the Gate test is a class declared in `MorphAliasesTest.php`, not a new fixture file, because the group's file list has no fixture. Group 11's untracked `DenyingOldKeyMcpInstructionPolicy.php` was left alone.

## Group 11

Policy-key remap (AC 7). Cortex commit `0a78577` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`, built against the local siblings (`composer local:install`, Keystone `b8e3477`).

### Files

- `src/Domains/ConcreteServer/ConcreteServerServiceProvider.php`:
  - Group 10's private `MORPH_ALIASES` constant is renamed `OLD_MODEL_NAMES`. The two entries are unchanged. The morph aliases, the morph check and the policy remap all use it.
  - `remapOldPolicyKeys()` (public, `@internal`) is called first thing in `register()`. It reads `cortex.policies` through `package()->configKey('policies')`, the key `registerPolicies()` reads. Each entry keyed by an old model FQCN moves to the new model class and keeps the host's policy class. Matching ignores case and a leading backslash, as PHP class names do. When the host also lists the new key, the new key wins and the old entry is dropped. The method writes the config back only when it moved something. Every provider registers before any provider boots, so the Cortex provider's `registerPolicies()` in `boot()` already sees the new keys. That holds whatever order the providers boot in.
  - The remap also calls `class_exists($old)` on each old model key it finds, which defines the lazy shim alias. Without it, a host policy that type-hints the old model name fails with a `TypeError`: PHP's parameter type checks never autoload. I checked this by taking the call out, and the denying-policy test then answered 500 with that `TypeError`. D-027 still holds, because the alias is defined only when the host names the old class.
  - `warnAboutOldPolicyKeys()` (public, `@internal`) is called first thing in `boot()`. For each key the remap moved, it logs one `Log::warning`. The message names the old and new keys, says the key is deprecated, and says what happened: the policy now applies to the new key, or the new key is listed too and wins. The context holds `old` and `new`. It then clears the list, so each key is logged once per boot. The logging waits for `boot()` because the `service-providers` standard keeps `register()` away from resolved services, and a host log channel may depend on a provider that has not registered yet.
  - Old policy FQCNs given as values are not rewritten. The Gate resolves them through the container, so the lazy shims alias them.
- `src/CortexServiceProvider.php`: a comment above `registerPolicies()` says the old keys were already moved by `ConcreteServerServiceProvider::register()`. There is no code change. The comment avoids the literal `Domains\McpServer` string, so group 12's allowlist does not need this file.
- `tests/Fixtures/Policies/DenyingOldKeyMcpInstructionPolicy.php`: the fixture that was already in the worktree untracked, committed unchanged. It type-hints the old model FQCN, allows `viewAny`/`view` and denies `create`/`update`/`delete`.
- `tests/Feature/Compat/OldPolicyKeysTest.php` (new).

### Tests

- `tests/Feature/Compat/OldPolicyKeysTest.php` (7 tests). The host config is the bundled `cortex.policies` map minus the two new model keys, plus the old keys, as in a config published before the rename. The helper `bootWithHostPolicies()` sets that config, then runs `remapOldPolicyKeys()`, `warnAboutOldPolicyKeys()` and the Cortex provider's `registerPolicies()`.
  - A denying host policy keyed by the old instruction model FQCN, with the old version-policy FQCN as the value for the old version key:
    - `Gate::getPolicyFor()` returns the host policy.
    - Show is allowed over HTTP and MCP, which exercises the old-model type-hint.
    - Creating a version (HTTP 401, MCP `Unauthorized.`) and deleting the instructions (HTTP 401, MCP `Unauthorized.`) are refused instead of falling back to the bundled allow-all policy.
    - No version is written and the row stays.
  - The config afterwards has no old keys, and the new keys hold the host's values.
  - One warning per remapped key: `Log::spy()` checks that each key's warning names the old and new keys in the message and in the `old`/`new` context, and that there are exactly two warnings. A second `warnAboutOldPolicyKeys()` call logs nothing more.
  - Both keys listed (old key denying, new key the bundled policy): the new key's policy is registered, the old key is gone from the config, one warning names the old key, and a guest create answers 201.
  - Old policy FQCNs as values, under an old key and under a new key: each resolves to the new policy class (`ReflectionClass::getName()`), and a create answers 201.
  - A config with no old keys stays unchanged and logs nothing.
  - Wiring: `new ConcreteServerServiceProvider(app())->register()` remaps the config itself, and `warnAboutOldPolicyKeys()` on that instance logs the warning.
- I saw them fail first: 6 errored because `remapOldPolicyKeys()` was undefined, and the wiring test failed because the old key was still there.
- `composer test:unit`: 409 of 410 pass. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium. `composer analyse`, `composer lint:check` and `composer test:types` (100%) pass.

### Deviations

- Two `@internal` public methods on the provider (`remapOldPolicyKeys()`, `warnAboutOldPolicyKeys()`), so the tests can run the remap on a booted app. This follows group 10's `checkMorphAliases()`.
- The full-boot ordering is not tested with a separate app booted from a host config. Testbench's `defineEnvironment()` lives in the shared `TestCase`, which group 11 does not own. The wiring test calls `register()` on a fresh provider instead. That `register()` runs before every `boot()` is Laravel's own guarantee.
- Group 12's allowlist needs `tests/Feature/Compat/OldPolicyKeysTest.php` and `tests/Fixtures/Policies/DenyingOldKeyMcpInstructionPolicy.php`, plus the provider, which group 10 already noted.

## Group 12

Keen and CI checks (AC 2, 3, 6, 22). Cortex commit `3443e0d` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`, built against the local siblings (`composer local:install`, Keystone `b8e3477`, Keen symlinked).

### Files

- `tests/Feature/Compat/OldNamespaceReferencesTest.php` (new). This is the AC 2 scan (D-040). It reads every file under `src/` and `tests/` and matches the old namespace with one or more backslashes between `Domains` and `McpServer`, ignoring case, so it catches both the `use` form and the escaped string form. It never spells the old name itself, so it is not on its own allowlist. The allowlist is a constant holding exactly nine named files:
  - group 2: `src/Domains/ConcreteServer/shims.php`, `tests/Feature/Compat/ClassShimsTest.php`, `tests/Fixtures/OldNamespaceServer.php`, `tests/Fixtures/OldTraitServer.php`, `tests/Fixtures/old-namespace-child.php`;
  - groups 10 and 11: `src/Domains/ConcreteServer/ConcreteServerServiceProvider.php`;
  - group 10: `tests/Feature/Compat/MorphAliasesTest.php`;
  - group 11: `tests/Feature/Compat/OldPolicyKeysTest.php`, `tests/Fixtures/Policies/DenyingOldKeyMcpInstructionPolicy.php`.
- `tests/Feature/Compat/KeenInCiTest.php` (new). It holds the Keen guard (AC 3, D-041) and the composer and workflow checks for 12.2.
- `.github/workflows/tests.yml`: a new `Dump autoloader` step (`run: composer dump-autoload`) right after `Install dependencies` and before PHPStan, the linter, type coverage and both test-suite steps. It has no `if`, so it runs on Ubuntu and Windows (D-038).
- `composer.json` and `composer.lock` are unchanged (12.2 confirmed). `refactor-circus/keen: dev-main` is in `require-dev`, `composer.lock` `packages-dev` lists it (`9da4b1f`), and `@composer dump-autoload` is the first entry of `scripts.test`.

### Tests

- `OldNamespaceReferencesTest` (3 tests):
  - The allowlist has exactly 9 entries with no duplicates, and each file exists.
  - The files under `src/` and `tests/` that name the old namespace are exactly the allowlist. One message lists files that name it without being on the allowlist (a leak); another lists allowlisted files that no longer name it (a dead entry). So the list can neither grow nor go stale without someone editing the test.
  - The scanner finds a plain `use` and an escaped string in a nested folder and skips a clean file. This runs against a temporary tree, so the scan cannot pass vacuously.
- `KeenInCiTest` (11 tests, counting the dataset):
  - The guard: when CI is set, the test fails unless `class_exists(AuditEntryModel::class)` holds, and the failure message names the class and `refactor-circus/keen`. When CI is not set and Keen is missing, it skips. Otherwise it passes. CI counts as set for any value except empty, `0` and `false`. The value is read with `getenv('CI')`, then `$_SERVER` and `$_ENV`.
  - A dataset of seven cases covers the guard's decision: `true`/`1` with Keen missing fail; `true` with Keen installed passes; unset, empty, `false` and `0` with Keen missing pass. This is how the CI failure path is proven locally, where Keen is installed.
  - `composer.json` keeps Keen in `require-dev` (and not in `require`), and `composer.lock` `packages-dev` lists it.
  - `scripts.test[0]` is `@composer dump-autoload`.
  - The workflow has a `run: composer dump-autoload` step after the `composer update` install and before `run: composer test:unit` and `run: vendor/bin/pest`.
- Seen failing first: the workflow test failed ("The CI workflow has no step matching …dump-autoload…") before the step was added. The scan and the Keen guard passed from the start, because groups 2, 10 and 11 had already put the code and fixtures in place.
- `composer test:unit`: 423 of 424 pass. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium. `composer lint:check` passes and `composer test:types` is 100%.

### Deviations

- `composer.json` and `composer.lock` are in the group's file list but needed no change. Group 9 and group 2 had already done 12.2's composer part.
- The guard test runs on the real environment, so locally (Keen installed, CI unset) it passes rather than skips. The CI-set, Keen-missing failure is proven through the guard's decision function, as a dataset.
- Later groups: a new file that names the old namespace fails this scan. Group 5's `tests/Feature/Compat/OldNamespaceToolOverrideTest.php` should reach the old-namespace servers through the allowlisted `Tests\Fixtures\OldNamespaceServer` and `OldTraitServer`, not by spelling the old name. If it really must spell it, group 5 has to add the file to the allowlist and raise the count.
- CI still installs Keystone from VCS at `dff82dd` (before D-052). Releasing Keystone and raising the constraint is still the pre-merge step from group 1.

## Group 3

Tools column, registry and tool-override storage (AC 8, 12, 16, 18, 22). Cortex commit `9dbb2ba` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`, built against the local siblings.

### Files

- `database/migrations/2026_10_23_000002_add_tools_to_cortex_mcp_instructions.php` (new): a nullable json `tools` column on `Tables::name('mcp_instructions')`. Both `up()` and `down()` check `Schema::hasColumn()` first (D-042), so running either twice does nothing.
- `src/Domains/ConcreteServer/Models/McpInstructionModel.php`: `tools` added to `$fillable`, an `array` cast in `casts()`, and the `@property list<string>|null $tools` docblock.
- `src/Domains/ConcreteServer/Services/McpServerRegistry.php`:
  - `defaultTools(string $name): list<string>` reads the `$tools` default by reflection without building the server. It flattens `ToolSearch` groups (and any array entry) in declaration order and names each tool class by `name()`, built through the container.
  - `supportsToolOverrides(string $name): bool` throws `McpServerNotFoundException` for an unknown name, like `AgentRegistry`, and is false only for the name `cortex` (now a private `CORTEX` constant).
- `src/Domains/ConcreteServer/Services/McpToolOverrides.php` (new, scoped): `for(string $server): ?array`.
  - It maps every row with non-null `tools`, published or not, cached under `mcpToolsKey()` and memoized per request.
  - A decoded value that is not an array reads as null, with a limited warning (`server`, `value_type` = `get_debug_type()`, limiter subject `value_type:<type>`). A json `null` reads as null with no warning. Non-string items inside a list are dropped.
  - When the column is missing it returns null without querying. It logs a limited warning with context `['server' => …]` (subject `column:tools`) whose message names the table, the `cortex-migrations` tag and the `2026_10_23_000002_add_tools_to_cortex_mcp_instructions` migration.
- `src/Domains/ConcreteServer/Services/ToolsColumn.php` (new, singleton): `exists()` asks the model's connection (`McpInstructionModel::getConnection()->getSchemaBuilder()->hasColumn()`). It remembers the answer per connection name and table for the life of the singleton, which means per worker process. It only stores a completed check, because a throw propagates (see Decisions).
- `src/Support/WarningLimiter.php` (new): `warn(string $server, string $subject, string $message, array $context = []): bool`.
  - It claims `PublicationCache::warningKey($server, $subject)` with one atomic `add()` on the publication cache's store, for `max(1, (int) (config('cortex.mcp.warning_ttl') ?? 3600))` seconds.
  - If the claim throws, it logs anyway, and it never throws itself.
  - Groups 4 and 5 use it for the stale, failing, ambiguous and rebuild-failure warnings (rebuild failure: subject = exception class).
- `src/Support/PublicationCache.php`: new `mcpToolsKey()` (`{cortex.published}.mcp-tools`) and `warningKey()` (`{cortex.published}.warning.<xxh128(server)>.<xxh128(subject)>`, a fixed length of 92 characters). Also a public `@internal store()` that returns the repository the cache already resolves (pinned store, else Redis when it answers, else default), so limiter claims are shared across nodes.
- `src/Domains/ConcreteServer/ConcreteServerServiceProvider.php`: `scoped(McpToolOverrides::class)` and `singleton(ToolsColumn::class)`.
- `config/cortex.php`: `mcp.warning_ttl => 3600` under its own "MCP Warning Rate Limit" banner inside the `mcp` section. The banner says what the key controls, the default, the clamp to 1 and the shared-store requirement.
- `tests/Fixtures/GroupedToolsServer.php` (new): one plain tool plus a `ToolSearch` group.
- `tests/Fixtures/NamedMcpTool.php` (new, **not in the group's file list**): `#[Name('renamed-echo')]`. No existing fixture tool had a `name()` that differs from its class basename, so without it the "uses `name()`" assertion could not tell the two apart.

### Tests

- `tests/Unit/McpServerRegistryDefaultToolsTest.php` (9; boots Testbench only for the container):
  - flattened declaration order;
  - `renamed-echo` and not `named-mcp-tool`;
  - `defaultTools('cortex')` equals the `name()` of every `CortexServer::TOOLS` entry;
  - an empty list for a server without tools;
  - an unknown server throws;
  - `supportsToolOverrides()` is false for `cortex` and true otherwise;
  - `CortexServer` registered as `cortex-copy` is overridable;
  - the server class is never built (its container binding throws).
- `tests/Feature/ToolsMigrationTest.php` (4):
  - Staged on its own in-memory `staged` connection (D-024, D-031): the target is found by glob, every earlier file runs with one `Artisan::call('migrate', ['--path' => …, '--realpath' => true])` each, a row is inserted, the target runs and the row's `tools` is null. A `migrate:rollback --step=1` on the target then drops the column and keeps the row.
  - `up()`/`up()`/`down()`/`down()`/`up()` on the suite database never fails and leaves zero or one `tools` column.
  - The cast and `$fillable`.
  - `[]` is stored as `'[]'`, apart from null.
- `tests/Feature/McpToolOverridesTest.php` (16 with datasets; `beforeEach` flushes the array store):
  - every non-null row, published or not;
  - stored order;
  - `[]` versus null;
  - cached under `mcpToolsKey()` until it is forgotten;
  - the key's `{cortex.published}` hash tag is shared with its flexible twin;
  - read once per request (scoped);
  - corrupt string, int and bool values read as null with exactly `['server', 'value_type']` (`Log::spy()`);
  - the corrupt warning is limited across requests and logs again after a cache flush;
  - with a real throwing `Store` driver, the map is still served and the warning still logged;
  - without the column: no tool override, the instructions override is still applied, and the warning names the migration with exactly `['server']`; it is limited across requests and logs again after time travel past the configured TTL;
  - the column check is memoized: after the column is dropped it still answers true with no query, and a new singleton answers false;
  - the check uses the model's connection: a `ConnectionResolver` whose default is a `tenant` connection without the column makes the model's connection differ from `DB::connection()`; the check answers false there and true on the default connection.
- `tests/Feature/WarningLimiterTest.php` (14 with datasets; `beforeEach` flushes the array store and freezes time):
  - logs once with its context, then suppresses;
  - time travel to just under and past the configured 120 s;
  - the string `'30'` is cast to an int;
  - an absent key falls back to 3600, tested by travelling around it;
  - the shipped config has 3600;
  - 0, -5 and `'not a number'` are clamped to 1 second (otherwise `Cache::add` would refuse every claim);
  - entries and servers are limited independently;
  - a claim another worker already holds suppresses the warning;
  - keys carry the prefix, have a bounded, fixed length and keep the server and subject apart (`ab`+`c` ≠ `a`+`bc`);
  - claims follow `cortex.cache.store`;
  - with a real throwing `Store` registered through `Cache::extend`, the warning is logged on every call and the store saw exactly `['add', 'add']` (atomic add, never get-then-put).
- I wrote the tests before the code, but I did not run them on their own first. They failed to load because `WarningLimiter`, `McpToolOverrides`, `ToolsColumn`, `mcpToolsKey()`/`warningKey()`, `defaultTools()`/`supportsToolOverrides()` and the migration did not exist yet.
- The 4 new files: 41 tests pass.
- `composer test:unit`: 464 of 465 pass, in two random-order runs. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium. `composer analyse`, `composer lint:check` and `composer test:types` (100%) pass.

### Deviations

- AC 12 says "a host subclass of `CortexServer` registered under another name is overridable", but `CortexServer` is `final` and is not in this group's files. The test registers `CortexServer::class` itself under `cortex-copy`, which shows the same thing: the rule follows the registered name, not the class.
- `tests/Fixtures/NamedMcpTool.php` is outside the group's file list (reason above).
- The publication-cache standard says the cache holds only published content. The limiter puts no content through `remember()`. It only borrows the store (`store()`, `@internal`) and the key family, as AC 16 requires, so claims are shared across nodes and app-prefixed. Nothing is added to `docs/agent/standards-feedback.md`, because no rule is broken.
- `ToolsColumn` is memoized on a container singleton rather than a static. That is one answer per worker process under Octane and queues, and a fresh answer per test, so no test can leak a "missing" answer into later tests.

### Decisions

- Open (returned to RingMaster): what serving does when the column check itself throws. AC 8 leaves this as "the open question on that". Right now the throw propagates out of `ToolsColumn::exists()` and `McpToolOverrides::for()`, and nothing is memoized. Settled as D-055; see "Group 3 (D-055)" below.

## Group 3 (D-055)

Tasks 3.4 to 3.6: a throwing tools-column check reads as no tool override (AC 8, AC 16). Cortex commit `7e04d78` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`.

### Files

- `src/Domains/ConcreteServer/Services/McpToolOverrides.php`: the column check moved into a private `hasToolsColumn($server)`.
  - It catches any `Throwable` from `ToolsColumn::exists()`, logs through `WarningLimiter` and returns false, so `for()` answers null (serve the code tools).
  - The warning uses subject `column-check:<exception class>` and context `['server' => …, 'message' => <exception message>]`. The message names the server, the table and the exception class, and says the check runs again on the next request.
  - The missing-column branch is unchanged (subject `column:tools`, context `['server']`). The class docblock now covers the throwing case and says the instructions override still applies.
- `src/Domains/ConcreteServer/Services/ToolsColumn.php`: docblock only. A throwing check still propagates and stores nothing, because the `??=` assignment never runs, and `McpToolOverrides` turns it into "no override". The code did not change.

### Tests

- `tests/Feature/McpToolOverridesTest.php`, new `describe('when the column check throws')` (2 tests). The schema query is made to throw with a switchable `DB::connection()->beforeExecuting()` callback that throws `RuntimeException('Connection is down.')`:
  - Two calls within the TTL both answer null. The schema query ran twice, so nothing was memoized, and the `ToolsColumn` singleton stayed the same instance. Exactly one warning was logged, with context `['server' => 'echo', 'message' => 'Connection is down.']`, and the limiter key `warningKey('echo', 'column-check:RuntimeException')` is held. Once the switch is off, the next call returns the stored `['echo-mcp-tool']` and the same singleton's `exists()` answers true. No second warning is logged.
  - With the instructions map already warm, a throwing check answers no tool override, and `McpInstructionOverrides::for('echo')` still returns the published instructions. The switch is turned off in `finally` so the test's own rollback can reach the database.
- I ran the tests first and both failed with the propagated `Connection is down.` from `ToolsColumn.php:34`. With the code in place, all 17 tests in the file pass.
- `composer test:unit`: 466 of 467 pass. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium. `composer lint:check`, `composer analyse` and `composer test:types` (100%) pass.

### Deviations

- None from the standards. The spec says "context: server name and the exception message" but names no keys, so I chose `message` as the key for the exception message. Group 5's throwing-entry and rebuild-failure warnings should use the same key.
- The `tools/list` half of the D-055 test (code tools served, instructions override in the served context) needs the serving path, so it belongs to group 5 (task 5.1: "missing column serves code tools …"). Group 3 proves it at the `McpToolOverrides::for()` and `McpInstructionOverrides` level.

## Group 4

Update, clear and delete actions with policy and boot warning (AC 9, 9a, 11, 12, 13, 17). Cortex commit `1385757` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`, built against the local siblings.

### Files

- `src/Domains/ConcreteServer/Data/UpdateConcreteServerToolsData.php` (new): `?array $tools` (`list<string>|null`), `rules(string $server)`.
  - `tools` is `present|nullable|array|list`. `list` was added so a keyed object cannot be stored as a JSON object.
  - `tools.*` is `string|distinct|in:` the server's `defaultTools()` plus `ToolRegistry::names()`, so an unknown entry fails on `tools.N`.
  - A closure on `tools` works out each entry's served name: the entry itself for a code tool, which wins when both match (D-021), otherwise `ToolName::of()` of the registered class. A registered tool that cannot be built is named by its entry. The closure fails the later entry of a pair with `$fail("tools.N", …)`.
- `src/Domains/ConcreteServer/Data/ClearConcreteServerToolsData.php` (new): no fields, and `rules(string $server)` returns `[]`. The server comes from the route or tool argument.
- `src/Domains/ConcreteServer/Events/ConcreteServerTools{Updating,Updated,Clearing,Cleared}ActionEvent.php` (new):
  - Updating carries `string $server` and the Data.
  - Updated carries `McpInstructionModel $instruction`.
  - Clearing carries `string $server` and the Data.
  - Cleared carries `string $server`, because the clear returns `void` and the row may be gone.
- `src/Domains/ConcreteServer/Actions/UpdateConcreteServerToolsAction.php` (new). `execute(string $server, UpdateConcreteServerToolsData $data): McpInstructionModel`.
  - Domain guard: `supportsToolOverrides()` false (the `cortex` server) throws `ValidationException::withMessages(['server' => …])`, a 422. An unknown server throws `McpServerNotFoundException`.
  - Inside `DB::transaction()` it re-reads the row with `where('server')->lockForUpdate()->first()`, or starts a new model when there is none, then sets `tools` and saves (D-049).
  - A `UniqueConstraintViolationException` from a colliding first save is retried once, in a new transaction. The retry finds the row and updates it.
  - Null is stored as given. The row is created even then, as AC 9's "creating it when missing" reads.
- `src/Domains/ConcreteServer/Actions/ClearConcreteServerToolsAction.php` (new). `execute(string $server, ClearConcreteServerToolsData $data): void`.
  - The `cortex` guard is the same as the update's.
  - It re-reads the row under `lockForUpdate()`. There is no row, or `tools` is already null: nothing is written and nothing is forgotten.
  - The row has no `published_version_id` and no versions: the row is deleted, and both keys are forgotten.
  - Otherwise `tools = null` is saved and `mcpToolsKey()` is forgotten. Instructions, versions and the pointer are never touched.
- `src/Domains/ConcreteServer/Actions/DeleteMcpInstructionAction.php`. Same signature and events as before.
  - Inside the transaction it re-reads the row by key with `lockForUpdate()`.
  - It deletes the versions one model at a time (`lazyById()`, each `$version->delete()`, D-029).
  - Then it deletes the row when `tools` is null, otherwise it nulls `published_version_id`.
  - It forgets `mcpInstructionsKey()` and `mcpToolsKey()` after commit in every case, including when the row was already gone.
- After-commit forgets: all three actions use a private `forget()` that wraps each key in `try`/`catch (Throwable)`. On failure it calls `Log::warning` with context `key` and `message`, and the action's response and committed write stand (AC 17).
- `src/Domains/ConcreteServer/Policies/McpInstructionPolicy.php`: `updateTools(?Authenticatable, McpInstructionModel): bool` returns true. Its docblock covers four points:
  - where the ability is checked;
  - that the bundled default allows guests;
  - the trust boundary (D-033) and the boot warning;
  - that a host policy without the method denies.
  
  It ends with a deny-by-default snippet that contains `return false`.
- `src/Domains/ConcreteServer/Services/GuestToolsWarning.php` (new). `check()` and a static `reset()` (`@internal`), guarded by a once-per-process static flag (D-037).
  - It skips the console and `local`, and wraps everything in `catch (Throwable)`.
  - Overridable servers are `names()` filtered by `supportsToolOverrides()`.
  - When `cortex.policies[McpInstructionModel]` is the bundled `McpInstructionPolicy` class, every overridable server counts and the Gate is not asked.
  - With any other policy it asks `Gate::forUser(null)->allows('updateTools', new McpInstructionModel(['server' => $name]))` for each server. That is an unsaved model, so there is no query.
  - It logs one `Log::warning` that names `updateTools` and points to the README section, with context `['servers' => [...]]` (D-039).
- `src/Domains/ConcreteServer/ConcreteServerServiceProvider.php`: `boot()` adds `$this->app->booted(fn () => $this->app->make(GuestToolsWarning::class)->check())`.
- `config/cortex.php`: the Policies banner gains an `updateTools` entry. It says the bundled default allows guests, describes the boot warning and that a policy without the method denies, and carries the deny-by-default snippet.
- `tests/Fixtures/Policies/NoUpdateToolsMcpInstructionPolicy.php` and `PartialUpdateToolsMcpInstructionPolicy.php` (new). The partial one allows `updateTools` only for `$instruction->server === 'echo'`.
- `tests/Support/FileDatabaseMigrations.php` (new, **not in the group's file list**). It is a trait for `uses()`:
  - It wraps Laravel's `DatabaseMigrations`.
  - Its `defineEnvironment()` points the `testing` connection at a `tempnam()` SQLite file.
  - Its `defineDatabaseMigrations()` gives the migrator the package's migrations path, so `migrate:fresh` and `migrate:rollback` run Cortex's migrations instead of Testbench's loader.
  - It unlinks the file after the rollback. Testbench runs `beforeApplicationDestroyed` callbacks last-registered first, so the unlink is registered before the migrations.
- `tests/Feature/EventsTest.php` (**not in the group's file list**): the action count in "gives every action exactly one start and one finish event" goes from 34 to 36 for the two new actions. No other change.

### Tests

- `tests/Feature/ConcreteServerToolsActionsTest.php` (30 tests).
  - Update rules, checked through `UpdateConcreteServerToolsData::from()`, which throws on bad input:
    - `present`, `array` and `list` failures; null and `[]` pass;
    - code tools by `name()` plus registered tools; the class basename fails;
    - an unknown entry gives only `tools.1`, and a non-string fails;
    - a repeated entry fails;
    - a duplicate served name gives only the later index, both for a code tool against an alias and for two registrations of one class;
    - an entry matching both a code and a registered tool passes;
    - a server without code tools accepts registered tools only;
    - `rules('missing')` throws.
  - Update action:
    - it creates the row in stored order, and an update keeps the row's id, pointer and versions;
    - `[]` is stored raw as `'[]'`, and null as NULL;
    - a row created by a starting-event listener is updated rather than duplicated (the decision comes from the in-transaction re-read);
    - `cortex` gives a 422 on `server` with nothing written, and `CortexServer` under `cortex-copy` is overridable;
    - an unknown server throws;
    - the events carry the server and Data, then the model.
  - Ability: the bundled policy allows `updateTools` for guests and for a signed-in user. With `NoUpdateToolsMcpInstructionPolicy`, `update` is allowed and `updateTools` is denied for both.
  - Clear:
    - `tools` becomes null while the pointer and both versions stay;
    - a tool-only row is removed;
    - a version created by a `ConcreteServerToolsClearingActionEvent` listener keeps the row (flag asserted);
    - a set pointer alone keeps the row;
    - with no row or `tools` already null, it is a no-op (`updated_at` unchanged after time travel);
    - `cortex` gives a 422 and its row is unchanged;
    - an unknown server throws;
    - the Data takes no input, and both events are dispatched.
- `tests/Feature/ConcreteServerToolsRaceTest.php` (2, `FileDatabaseMigrations`).
  - The first test checks the mode itself: a SQLite file on disk and transaction level 0.
  - In the second, a `creating` listener on `McpInstructionModel` inserts a competing `grouped` row once (flag asserted). The action succeeds, there is one row, and it holds the saved list.
- `tests/Feature/ConcreteServerToolsCacheTest.php` (14 with datasets, `FileDatabaseMigrations`).
  - The publication cache is pinned to a real `ArrayStore` subclass registered with `Cache::extend`. It records `DB::transactionLevel()` and the key at every `forget`, and can be made to throw.
  - Update, clear and delete each have:
    - (a) a fresh read after the action;
    - (b) forgets recorded, every level 0, and the exact keys. Clear forgets `mcpToolsKey()` alone when the row stays and both keys when it goes. Delete forgets both keys whether the row stays or goes;
    - (c) a rollback, forced by a `saved` listener for update and clear, or a `deleting` listener that throws on the second version for delete. The database is unchanged, no forget is recorded, and the next read returns the old value. For delete the row, its pointer and all 3 versions stay.
  - A throwing `forget` leaves each action successful, with its write committed, and logs the warning per key (`key`, `message`).
- `tests/Feature/DeleteMcpInstructionActionTest.php` (8 with datasets). Each case builds its override through the version actions and the update action.
  - Instructions plus tools: the row stays with the same id, the pointer is null, the versions are gone and `tools` is kept. `McpToolOverrides` still serves the list, `McpInstructionOverrides` is null and `McpServerRegistry::all()` reports the code instructions.
  - A row with `[]` stays.
  - A row with null tools is removed together with its versions.
  - Each version fires its own `McpInstructionVersionDeleting/DeletedEvent`, whether or not the row stays.
  - Keen (`->skip(! class_exists(...))`, and it ran here): exactly one `mcp_instruction.deleted` audit entry for the instruction.
  - Race: a starting-event listener writes `tools` with the query builder (flag asserted). The row and its tools stay, and no versions remain.
  - A row that is already gone: no error.
- `tests/Feature/GuestToolsWarningTest.php` (10). `beforeEach` calls `GuestToolsWarning::reset()` and registers `echo` and `plain`.
  - Fresh boot: a file-local trait overrides `defineEnvironment()`. While a static switch is on, it sets the app's `isRunningInConsole` to false and captures `MessageLogged`. The test calls `$this->createApplication()` and then hands the container and facades back to the test app. The booted callback logs exactly one `updateTools` warning with `servers` = `['echo', 'plain']`.
  - With the bundled policy, the warning names every overridable server and points to the README. The Gate is never asked (a `Gate::before` counter stays at 0).
  - Nothing is logged in `local`, in the console, with the no-`updateTools` policy, or with a throwing policy (which raises no exception either).
  - With the partial policy, `servers` is `['echo']`.
  - `cortex` is never named, and `cortex-copy` is.
  - A second call logs nothing, and `reset()` lets the check run again.
- Seen failing first: all five files failed before the code existed, because the Data, actions, events and service classes were missing.
- `composer test:unit`: 530 of 531 pass. The one failure is the accepted D-054 ArchTest "preset → security". `composer lint:check`, `composer analyse` and `composer test:types` (100%) pass.

### Deviations

- Actions follow the domain's existing shape: a `final` class with `execute()` and a private `perform()`, not Keystone's `Action` base with `protected handle()`. That base is what the `actions-events` standard and CLAUDE.md describe, but no Cortex action uses it, and `tests/ArchTest.php` checks `execute()`. Moving to the base would be a suite-wide change, not this group's.
- Authorization is not inside the actions. Every Cortex action leaves it to the HTTP and MCP requests (`authorization` standard: requests authorize in `authorize()`). The group adds and tests the `updateTools` ability. Group 6's update and clear requests and MCP requests must call `$this->allows('updateTools', McpInstructionModel::query()->firstOrNew(['server' => …]))`. That subject has the same shape the boot warning asks about.
- The `cortex` refusal is a domain guard in both actions, keyed `server`, rather than a rule on `tools`. The clear has no input field that could carry it, and the guard also covers callers that build the Data with `new`.
- Keen audit for version deletes: Keen records action events only (`AuditRecorder` listens to `ActionStartingEvent`/`ActionFinishedEvent`), so there is no audit entry per version. The test asserts the per-version model events and the one `mcp_instruction.deleted` entry. Returned as a decision.
- Two files are outside the list: `tests/Support/FileDatabaseMigrations.php`, needed by the two D-023 files, and the count in `tests/Feature/EventsTest.php`.
- `UpdateConcreteServerToolsData` adds `list` to `present|nullable|array`.

## Group 5

Serving the overridden tool list (AC 6, 8, 10, 10a to 10f, 12, 16, 18). Cortex commit `3f708e5` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`, built against the local siblings (`composer local:install`).

### Files

- `src/Domains/ConcreteServer/Concerns/HasCortexOverrides.php` (new). Its `createContext()` does nothing for a server class that is not registered (it returns `parent::createContext()`). For a registered server it builds the context once per request through `ServerContextMemo`:
  - it applies the published instructions override as before;
  - when `supportsToolOverrides()` holds and `McpToolOverrides::for()` returns a list, it rebuilds the context with the tools `ServerToolResolver::serve()` returns;
  - when anything in the rebuild throws, it serves the context with the code tools and the instructions override (D-028). It logs through `WarningLimiter` with subject `rebuild:<exception class>` (D-047) and context `['server', 'message']`. The message names the exception class.
- `src/Domains/ConcreteServer/Concerns/HasVersionedInstructions.php`: now only `use HasCortexOverrides;` (the kept name delegates).
- `src/Domains/ConcreteServer/Support/Server.php`: uses `HasVersionedInstructions`, which brings in `HasCortexOverrides` (see Deviations).
- `src/Domains/ConcreteServer/Services/ServerToolResolver.php` (new).
  - `resolve(array $codeTools, list<string> $entries)` does not log. It returns `tools` (in stored order), `missing` (entries that resolve to nothing or whose tool threw), `failed` (entry => exception message) and `ambiguous`.
  - Code tools come from the parent context's declared `$tools`. Groups are flattened, class names are built through the container, and each tool is keyed by `name()`, the first of a name winning. So constructor and `boot()` tools count (AC 10c), and a `ToolSearch` server serves a flat list (AC 10b).
  - Registered tools come from `ToolRegistry::summaries()` by registration name. MCP tool classes are built as they are; `Laravel\Ai` tools go through `AiToolAsMcpTool`, with the registry's published description override. A code tool wins over a registered tool (D-021).
  - A served name that appears twice is served once, which validation already prevents.
  - `serve($server, …)` logs through `WarningLimiter`, then returns the tools:
    - ambiguous: subject `ambiguous:<entry>`, context `['server', 'entry']`;
    - stale: subject `stale:<entry>`, context `['server', 'entry']`;
    - failed: subject `failed:<entry>`, context `['server', 'entry', 'message']`.
  - Group 6 can build `missing_tools` and `ambiguous_tools` from `resolve()`, which logs nothing (AC 15).
- `src/Domains/ConcreteServer/Services/ServerContextRebuilder.php` (new).
  - `tools(ServerContext)` reads the protected `$tools` by reflection.
  - `withTools(ServerContext, list<Tool>)` builds a new context of the same class. It passes every constructor parameter from the property of the same name, plus `tools` by name. A laravel/mcp change (a missing property, or a constructor without `tools`) therefore throws instead of silently serving the code tools, and the trait falls back.
- `src/Domains/ConcreteServer/Services/ServerContextMemo.php` (new). It stores one context per server class for the request (`remember($class, $build)`).
- `src/Domains/ConcreteServer/ConcreteServerServiceProvider.php` (**not in the group's file list**): `$this->app->scoped(ServerContextMemo::class)`, so the memo is a request-scoped container binding (AC 10f, `container-bindings`).
- `src/Support/AiToolAsMcpTool.php` (new, `@internal`, **not in the group's file list**; the spec names it `Support\AiToolAsMcpTool` but it did not exist). It is a `Laravel\Mcp\Server\Tool` wrapping a `Laravel\Ai\Contracts\Tool`:
  - `name()` comes from `ToolNameResolver`, as validation names it; `title()` is the headline of that name;
  - the description is the published override or the tool's own, and the schema is the tool's own;
  - `handle()` passes the MCP arguments as a `Laravel\Ai\Tools\Request` and answers with text;
  - `shouldRegister()` calls the wrapped tool's `shouldRegister()` through the container when it has one (true otherwise), so the tool's own gating sees the MCP request and its user (AC 10e).
- Fixtures (new): `ConstructorToolsServer` (declares `echo-mcp-tool` and `hidden-mcp-tool`, adds `renamed-echo` in its constructor and an `EchoRequestTool` instance in `boot()`), `HiddenMcpTool` (`shouldRegister()` false), `ThrowingCortexTool` (its constructor throws), `GatedAiTool` (a `Laravel\Ai` tool whose `shouldRegister(Laravel\Mcp\Request)` serves signed-in users only and records the user it saw), `CollidingCortexTool` (`#[Name('echo-mcp-tool')]`).
- `tests/Support/ServerContextTools.php` (new). The shared reflection helper (D-025): `of()` returns the context's raw `$tools`, `names()` their `name()`s in order, and `listed()` the ordered names of a test-client `tools/list` answer.

### Tests

- `tests/Feature/Mcp/ServerContextPinTest.php` (6). The vendor pin:
  - the `ServerContext` constructor's exact parameter list, every parameter promoted and required;
  - `$tools` is protected, not readonly and typed `array`, and `$instructions` is public and writable;
  - `ListTools` keeps the context's order, in two orders (D-051);
  - the helper reads what the rebuilder reads;
  - a context rebuilt from a real server keeps its instructions, implementation, capabilities, pagination, resources and prompts, leaves the source context alone, and serves `tools/list` and `tools/call` end to end.
- `tests/Feature/Mcp/ServerToolOverrideTest.php` (22 with datasets), all through the Laravel MCP test client:
  - the override lists exactly the stored tools, both code and registered, and each can be called;
  - with no row, a null list (written directly or through the action) or an instructions-only row, the code tools are listed;
  - calling a removed tool fails with `Tool [echo-mcp-tool] not found.`;
  - `[]` serves nothing;
  - a tool-only row is served, with the code instructions;
  - the override and the published instructions are served together;
  - stored order shows on the context (helper) and on `tools/list`, in two orders (AC 10a);
  - the flat `ToolSearch` list, with a grouped tool called directly (AC 10b);
  - constructor and `boot()` tools are served without an override and can be selected (AC 10c);
  - a hidden code tool and a hidden registered tool stay out of `tools/list` and `tools/call` (AC 10e);
  - the gated `Laravel\Ai` tool is hidden for a guest (its check ran and saw no user) and served for `actingAs(GenericUser(id 7))`, with the user reaching the check, and `tools/call` answers `gated: signed in`;
  - one context per request, and a new one after `forgetScopedInstances()`;
  - an update made in the same container is served from the next request, with a new memo instance (AC 10f);
  - a `cortex` row with tools is ignored on the registered web route (`cortex.mcp.web.enabled`, HTTP `tools/list` to `mcp/cortex` answers `search_tools`, `execute_tools`) and through `CortexServer::tools()` (AC 12);
  - any server registered under the name `cortex` ignores its row;
  - an unregistered server is untouched;
  - `Support\Server` and the kept trait name reach `HasCortexOverrides`.
- `tests/Feature/Mcp/ServerToolOverrideWarningsTest.php` (12). `beforeEach` flushes the array store, and logs are checked with `Log::spy()` against exact context arrays:
  - a stale entry is skipped, the rest is served and callable, and exactly one warning `['server', 'entry']` is logged; `resolve()` reports it in `missing` without logging;
  - a throwing registered tool, saved through the action, is skipped, the other tools are callable, one warning `['server', 'entry', 'message']` is logged, and it is reported in `missing` and `failed`;
  - a stale entry warns once across two requests within the TTL (`warning_ttl` set to 120) and again after time travel past the configured value;
  - entries are limited independently;
  - with a real throwing `Store` registered through `Cache::extend`, the tool is still served and callable, the warning is logged, and the store saw `add`;
  - ambiguity: a colliding tool registered after the save serves the code tool (`code wins`, never the colliding answer), logs one warning `['server', 'entry']`, and `resolve()` reports it in `ambiguous` without logging;
  - one client request with two ambiguous entries logs exactly one warning per entry;
  - building the context twice in one request (with the cache flushed in between) returns the same instance and does not warn again, and the next request warns again;
  - a corrupt value (`"echo-mcp-tool"`) serves the code tools and logs `['server', 'value_type' => 'string']` once (AC 18);
  - without the column, the code tools are served and callable, the instructions override is kept, and the warning naming the migration with `['server']` is logged once over two requests (AC 8);
  - a failing rebuild serves the code tools and the instructions override with a working `tools/call`, and logs `['server', 'message']` naming the exception class;
  - two requests within the TTL log once, and another exception class logs on its own (D-047).
- `tests/Feature/Compat/OldNamespaceToolOverrideTest.php` (2, a dataset). The old-base-class and old-trait fixtures (the allowlisted `Tests\Fixtures` classes, so the test does not spell the old namespace) list no tools. After a save through the update action they serve and call the registered tools, and after the clear action they serve their code tools again (AC 6).
- Seen failing first: 20 of the 42 new tests failed (missing `ServerContextRebuilder`, `ServerContextMemo`, `AiToolAsMcpTool`, and code tools served instead of the override). The rest were baselines that already held, such as code tools without a row and the `cortex` route.
- The new files: 42 tests pass. `composer test:unit`: 572 of 573 pass. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium. `composer analyse`, `composer lint:check` and `composer test:types` (100%) pass.

### Deviations

- Two files outside the group's list. `ConcreteServerServiceProvider` got the scoped binding for the memo; the `container-bindings` standard puts bindings in the owning provider's `register()`. `src/Support/AiToolAsMcpTool.php` is the adapter the spec names but nobody had written. I put it where the spec's naming (`Support\…`, like `Support\Tables`) points: the top-level `Support`. It is marked `@internal`.
- `Support\Server` uses `HasVersionedInstructions` (which uses `HasCortexOverrides`) rather than `HasCortexOverrides` directly. With nothing in `src/` using the kept trait name, PHPStan fails on `trait.unused`. The behaviour is the same, and `class_uses_recursive(Server::class)` includes `HasCortexOverrides`.
- The rebuild-failure tests force the failure by binding a `ServerContextRebuilder` factory that throws. The rebuilder is `final` and a laravel/mcp change cannot be simulated in-process. The pin test covers the reflection contract the real rebuild depends on.
- The memo stores the context for every registered server, not only overridden ones, so the corrupt-value and missing-column warnings also come once per request. A test or caller that changes an override inside one request sees the change after `forgetScopedInstances()`, like the existing scoped `McpInstructionOverrides`.
- `ServerToolResolver::resolve()` returns an array shape instead of a result class, to avoid one more file. Group 6 reads `missing` and `ambiguous` from it.

## Group 6

HTTP and MCP surfaces and resource payload (AC 9, 11, 12, 13, 14, 15). Cortex commit `627306c` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`, built against the local siblings (`composer local:install`).

### Files

- `src/Domains/ConcreteServer/routes.php`: `PUT servers/{server}/tools` (`cortex.servers.tools.update`) and `DELETE servers/{server}/tools` (`cortex.servers.tools.destroy`), in the existing route group, so they get the same prefix and middleware as the other server routes.
- `src/Domains/ConcreteServer/Http/Controllers/McpServerToolsController.php` (new): `update()` and `destroy()`. Each one only returns `$request->persist()`.
- `src/Domains/ConcreteServer/Http/Requests/UpdateConcreteServerToolsRequest.php` (new):
  - `authorize()` asks `updateTools` on `instructionOrNew()`, which is the server's row or an unsaved one naming it. That is the subject group 4 described.
  - `rules()` returns `UpdateConcreteServerToolsData::rules($server)`.
  - `persist()` builds the Data with `fromValidated()` and answers 200 with `McpInstructionResource`, even when the row is new (PUT sets state, like the concrete-agent tools PUT).
  - An unknown server answers 404 from `serverName()`. The `cortex` refusal is the action's `ValidationException` on `server`, so it answers 422.
- `src/Domains/ConcreteServer/Http/Requests/ClearConcreteServerToolsRequest.php` (new): the same authorization, `ClearConcreteServerToolsData::rules($server)`, then `fromValidated()`. It answers 204, as the `request-persist` standard asks of a delete.
- `src/Domains/ConcreteServer/Mcp/Requests/UpdateServerToolsMcpRequest.php` and `ClearServerToolsMcpRequest.php` (new). Both extend `ServerMcpRequest`, ask `updateTools` on `instructionOrNew()`, and use `serverRules()` plus the Data rules and `fromValidated($validated)`.
  - An unknown server answers `Not found.` through `ServerMcpRequest`.
  - The update answers `McpInstructionResource::toStructuredResponse()`.
  - The clear answers the text `Server tool override cleared. The server serves its code-declared tools.`, like `delete-server-instructions-tool`.
- `src/Domains/ConcreteServer/Mcp/Tools/UpdateServerToolsTool.php` (new): `server` plus `tools` (a nullable array of strings, required), with `outputSchema()` set to `McpInstructionResource::structuredSchema()`.
- `src/Domains/ConcreteServer/Mcp/Tools/ClearServerToolsTool.php` (new): `server` only. It answers text, so it has no `outputSchema()`.
- `src/Mcp/CortexServer.php`: both tools added to `TOOLS` after `DeleteServerInstructionsTool`. The server instructions now mention the tool override and both tool names.
- `src/Domains/ConcreteServer/Http/Resources/McpServerResource.php` and `src/Domains/ConcreteServer/Resources/McpInstructionResource.php`: four new fields, `tools`, `tools_overridden`, `missing_tools` and `ambiguous_tools`, all declared `required()` in `schema()`.
  - The list resource reads the stored list from `McpToolOverrides::for()`.
  - The instructions resource reads the row's own `tools`, so the PUT answer is never stale even when the request-scoped map was read earlier in the same request.
- `src/Domains/ConcreteServer/Services/ServerToolResolver.php` (**not in the group's file list**):
  - New `report(string $server, mixed $stored)`, which the two resources share. It does not log.
  - With no override, or with a value that is not a list, or on a server that is not overridable (`cortex`), it reports `tools` = `defaultTools()`, `tools_overridden` false and two empty lists.
  - Otherwise it runs `resolve()` against the class's declared tools and reports the served entries in stored order, `true`, `missing` and `ambiguous`.
  - An unregistered server (an orphan row) reports empty lists.
  - `resolve()` gains an `entries` key: the stored entry each served tool came from, in stored order.
  - The constructor now takes `McpServerRegistry`.
- `src/Domains/ConcreteServer/Services/McpServerRegistry.php` (**not in the group's file list**): new `declaredTools(string $name)`, which returns the raw `$tools` default (classes, instances and ToolSearch groups) by reflection. `defaultTools()` now reads it, with the same behaviour.
- `tests/Feature/Mcp/OutputSchemaTest.php` (**not in the group's file list**): the count of tools that answer structured content goes from 25 to 26 for `UpdateServerToolsTool`.

### Tests

- `tests/Feature/Http/ConcreteServerToolsTest.php` (20). Every request goes by route name, and the cache store is flushed in `beforeEach`.
  - PUT:
    - stores the list and answers 200 with the four fields;
    - null answers 200 with the code tools and `tools_overridden` false;
    - a refused entry answers 422 on `tools.1` only, and a missing key 422 on `tools`, with nothing stored;
    - `cortex` answers 422 on `server`;
    - an unknown server answers 404.
  - DELETE:
    - a tool-only row is removed (204), and the list shows the code tools again;
    - a row with published instructions keeps them, with `tools` null;
    - a server without an override answers 204;
    - `cortex` answers 422, and an unknown server 404.
  - Authorization: with `NoUpdateToolsMcpInstructionPolicy` (no `updateTools`), a guest gets 401 and a signed-in user 403 on both routes. The stored list is unchanged, and a server without a row is checked too.
  - Payload (AC 15), on the list and the show:
    - no row: the code tools, false, `[]`, `[]`;
    - a valid override in stored order (`renamed-echo`, `echo-cortex-tool`, `echo-mcp-tool`);
    - `[]` reported as an override that serves nothing;
    - a stale entry written directly is listed in `missing_tools` and left out of `tools`;
    - a `ThrowingCortexTool` entry is listed in `missing_tools`;
    - `CollidingCortexTool`, registered after the save under `echo-mcp-tool`, is listed in `ambiguous_tools`, and `Log::spy()` sees no warning;
    - a corrupt stored string reads as no override;
    - `cortex` with no row, and with a tools row inserted directly, reports its code tools and `tools_overridden` false on the list and the show.
  - AC 11: after versions are published and tools saved, `DELETE …/instructions` answers 2xx. Then the stored `tools` remain, the show payload has `published_version` null, the tools and `tools_overridden` true, and the list has the code instructions with `tools_overridden` true. The status is asserted only as 2xx; see Decisions.
- `tests/Feature/Mcp/ServerToolsToolsTest.php` (15 with datasets):
  - Both tools are in `CortexServer::TOOLS`, named `update-server-tools-tool` and `clear-server-tools-tool`.
  - Update parity, with time frozen, for valid entries, a throwing entry, `[]` and null: the HTTP PUT payload and row are captured, the row is deleted, and the MCP call answers `['data' => $http]` and stores the same row (tools, published version number, version numbers).
  - An ambiguous entry gives show parity, and the list has parity with the HTTP list.
  - The update errors on a refused entry and on `cortex` (the action's message), and answers `Not found.` for an unknown server.
  - Clear parity on a tool-only row (both remove it) and on a row with instructions (both keep it, with `tools` null and version 1 published). It succeeds without an override, errors on `cortex` and answers `Not found.` for an unknown server.
  - Both tools answer `Unauthorized.` under the policy without `updateTools`, and the stored list is unchanged.
  - `Parity::gaps()` over the ConcreteServer actions against `Http/Requests` and `Mcp/Requests` is `[]`.
- `tests/Feature/ToolOnlyRowTest.php` (8, with the dataset `saved through the update action` / `left by deleting the instructions`). Each case first checks that the row really is tool-only (`tools` set, pointer null, no versions). Then:
  - the server list shows the code instructions, the tools and `tools_overridden` true;
  - the show has `published_version`/`published_content` null and `tools_overridden` true;
  - the version list is `[]` over HTTP and MCP;
  - storing and publishing a version works, and the row keeps its tools.
  The fourth consumer, the Atrium screen, is group 7's.
- Seen failing first: 43 of the 43 new tests failed before the code (routes not defined, missing tool classes, no `tools` key, `Parity::gaps()` naming both new actions). The first run went through the main checkout's `vendor/bin/pest`, because the shell resets its directory. Later runs used `composer --working-dir=<worktree> exec -- pest`.
- The new files plus `OutputSchemaTest`: 44 pass. `composer test:unit`: 615 of 616 pass. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium. `composer lint:check`, `composer analyse` and `composer test:types` (100%) pass.

### Deviations

- Three files outside the group's list:
  - `ServerToolResolver` (`report()`, the `entries` key) keeps one resolution rule for serving and the payload.
  - `McpServerRegistry::declaredTools()` lets the payload resolve against the declared tools without building the server.
  - `OutputSchemaTest` has the count.
- Resource `tools` lists stored entries, not served `name()`s, while an override applies, so a caller can PUT back exactly what it reads. For code tools, and for registered tools whose registration name is their tool name, the two are the same. Without an override it lists the code tools by `name()` (`defaultTools()`).
- The payload resolves against the server class's declared `$tools`, as validation does (Design: "the reflection read … feeds validation and the screens"). A tool a host adds in its constructor or `boot()` is served when selected, but the payload reports it in `missing_tools`. Group 8's README note on dynamic tools should cover the payload too.
- The resources call `ServerToolResolver::report()`, and the list resource also calls `McpToolOverrides::for()`. Both are cached or in-memory reads, like `McpServerRegistry::all()` already does for instructions. The `resources` standard says a resource triggers no queries, but there is no model relation to eager-load for these fields. A cold cache costs one query for the tools map, and one for a `Laravel\Ai` tool's description override.
- "A server with no row reports … its code tools on list and show": the instructions show still answers 404 without a row (AC 2 baseline, `ServerInstructionToolsTest` "errors not found when no override exists yet"). So a server with no row is tested on the list, and `cortex` on the list and on the show of a directly inserted row.
- AC 15's "instructions-overridden flag" for tool-only rows is read as the existing fields (`published_version` null on the show, code instructions on the list). No new flag was added, because `schema()` is required to declare the four named fields only.
- MCP tool names follow the existing convention (`Str::kebab` of the class, with no `#[Name]`): `update-server-tools-tool` and `clear-server-tools-tool`, like `delete-server-instructions-tool`. The spec's `update-server-tools`/`clear-server-tools` are read as the same shorthand it uses for `delete-server-instructions`. Group 8's docs test can grep the stems, since they are substrings of the full names.

### Decisions

- Open (returned to RingMaster): what `DELETE cortex/servers/{server}/instructions` and `delete-server-instructions-tool` answer. AC 11 says the response "is the updated server resource showing `tools_overridden` true". Risks says "the exact response is the open question on it". The route answers 204 and the tool answers text today. The existing tests assert both (`McpInstructionsTest` and `RenamedTablesTest` `assertNoContent()`, `ServerInstructionToolsTest` `assertSee('Server instructions override deleted.')`), and AC 2 keeps their assertions unchanged. `DeleteMcpInstructionRequest` and `DeleteServerInstructionsMcpRequest` are not in this group's files, so the response is unchanged and only the state after the delete is tested. Closed by D-057; see the next section.

## Group 6 (D-057: instructions delete response)

Tasks 6.4 to 6.6, which also finish 6.1's DELETE-instructions assertions (AC 11, 14, 15). Cortex commit `e830c2f` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`.

### Files

- `src/Domains/ConcreteServer/Actions/DeleteMcpInstructionAction.php` (**not in the group's file list**; task 6.5 asks for it): `execute()` now returns `?McpInstructionModel`. It returns the row read under `lockForUpdate()` when the row survives because it holds a tool override, and null when the row was removed. The return comes from the locked read, so a tools write that landed after the action started (the race case) is reported as a survivor.
- `src/Domains/ConcreteServer/Http/Requests/DeleteMcpInstructionRequest.php` (**not in the list**, named by 6.5): `persist()` answers `noContent()` (204) when the action returns null. Otherwise it answers `McpInstructionResource` with status 200.
- `src/Domains/ConcreteServer/Mcp/Requests/DeleteServerInstructionsMcpRequest.php` (**not in the list**, named by 6.5): `handle()` answers the existing text `Server instructions override deleted.` when the row is removed. Otherwise it answers `(new McpInstructionResource($row))->toStructuredResponse()`.
- `src/Domains/ConcreteServer/Mcp/Tools/DeleteServerInstructionsTool.php` (**not in the list**):
  - `outputSchema()` is `McpInstructionResource::structuredSchema()`, for the structured branch.
  - The description now says that a tool override is kept, and what each branch answers.
- `src/Domains/ConcreteServer/Http/Controllers/McpInstructionController.php` (**not in the list**): `destroy()` now returns `Response|JsonResponse`. The controller still only returns `$request->persist()`.

### Tests

- `tests/Feature/Http/ConcreteServerToolsTest.php`:
  - new `describe('DELETE cortex.servers.instructions.destroy (D-057)')`:
    - an instructions-only row answers 204, and the row is gone;
    - an instructions+tools row answers 200 with the full payload: `server`, `published_version`/`published_content` null, `tools`, `tools_overridden` true, and empty `missing_tools`/`ambiguous_tools`.
  - The AC 11 test now asserts 200 and `data.tools_overridden` true, in place of `assertSuccessful()`.
- `tests/Feature/Mcp/ServerToolsToolsTest.php`, new `describe('delete-server-instructions-tool (D-057)')`:
  - an instructions-only row answers the text, carries no `structuredContent`, and the row is gone;
  - an instructions+tools row, with time frozen, gives parity between HTTP and MCP. The HTTP 200 payload is captured, the same row is prepared again, and the MCP call answers `['data' => $http]` as structured content. `mcpTool()` checks that answer against the tool's `outputSchema()`. The stored state is the same: tools kept, no published version, no versions.
- `tests/Feature/DeleteMcpInstructionActionTest.php` (**not in the list**):
  - the action returns the surviving row (tools kept, pointer null), or null when the row was removed;
  - the starting-event race test also asserts that the returned row carries the tools written concurrently.
- `tests/Feature/ToolOnlyRowTest.php`: the "left by deleting the instructions" setup asserts 200 and `tools_overridden` true.
- `tests/Feature/Mcp/OutputSchemaTest.php` (**not in the list**): the structured-tool count goes from 26 to 27, because `DeleteServerInstructionsMcpRequest` now calls `toStructuredResponse()`.
- Unchanged, and still passing: the existing 204/text assertions in `McpInstructionsTest`, `RenamedTablesTest` and `ServerInstructionToolsTest` (AC 2).
- Seen failing first: 6 of the new or changed assertions failed before the code (204 where 200 was expected, null where the action should return a row, a count of 26 where 27 was expected). The text-branch MCP test passed from the start, because it pins the existing behaviour.
- Results:
  - `composer test:unit`: 620 of 621 pass. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium.
  - `composer lint:check`, `composer analyse` and `composer test:types` (100%) pass.

### Deviations

- The `request-persist` standard asks for a 204 on a delete. The surviving-row branch answers 200 with the resource instead, as decided in D-057. The removed-row branch keeps the 204.
- Six files outside the group's list, all needed by task 6.5 and D-057: the delete action, both delete requests, the delete tool, `McpInstructionController::destroy()`'s return type, and the action and output-schema tests.
- The MCP tool declares `outputSchema()` but answers plain text in one branch. `mcpTool()` checks only structured answers, so the text branch passes it.

## Group 7

Atrium tool override editor (AC 9, 12, 15, 19). Cortex commit `034150a` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`.

### Files

- `src/Atrium/Http/Controllers/McpInstructionUiController.php`:
  - `show()` passes `toolEditor`: null for a server whose tools cannot be overridden (`supportsToolOverrides()`, so the `cortex` server), else the checklist items, the checked names, the served tools and whether an override applies. The checklist is the code tools in code order (`McpServerRegistry::defaultTools()`), then the registered tools in registry order, deduplicated, then the stored entries in `missing_tools` (from `ServerToolResolver::report()`) labelled `<name> (unresolved)`. A `missing_tools` entry that is also registered (its tool throws while built) keeps its place and gets the label.
  - `updateTools()` (PUT): 404 for an unknown server, `updateTools` checked on the override or an unsaved one (403 otherwise), an empty selection without `confirm_empty` refused with a validation error on `confirm_empty`, then `UpdateConcreteServerToolsData::from(array_merge($request->all(), ['tools' => $ordered]), $server)` and the update action. `$ordered` is the stored entries still ticked, in stored order, then the newly ticked ones in posted (checklist) order.
  - `clearTools()` (DELETE): the same 404 and `updateTools` checks, then `ClearConcreteServerToolsData::from($request->all(), $server)` and the clear action.
- `resources/views/ui/servers/partials/tools.blade.php` (new): the tools card. A status dot (overridden or from code). The first `tools`/`tools.*`/`server` error goes in an alert. With `updateTools`, it shows the save form: the shared `ui.partials.tool-picker` with `tools[]` checkboxes, the `confirm_empty` checkbox and a `save` submit. It shows a separate `restore` form only while an override applies. Without `updateTools`, it shows a read-only list of the served tools. Only `x-atrium::*` components and classes already used by other Cortex views.
- `resources/views/ui/servers/instructions.blade.php`: includes the partial when `$toolEditor` is not null.
- `src/Atrium/CortexPlugin.php` (**not in the list**): the two routes, `PUT cortex/servers/{server}/tools` (`atrium.cortex.servers.tools.update`) and `DELETE cortex/servers/{server}/tools` (`atrium.cortex.servers.tools.destroy`). The listed `src/Atrium/routes.php` does not exist: Atrium routes are declared in `CortexPlugin::routes()`.
- `lang/en/cortex.php` (**not in the list**): the editor's strings (hints, `unresolved`, the empty-list confirmation and its refusal message, restore label and confirmation, status message).
- `src/Atrium/ScreenAccess.php` and `src/Atrium/Http/Controllers/ServerUiController.php`: listed but unchanged. `ScreenAccess::serverInstruction()` already gives the subject the `updateTools` check needs.

### Tests

- `tests/Feature/Ui/ServerToolsEditorTest.php` (new, 16 tests). A helper reads every `tools[]` checkbox from the page in order (value, checked attribute, label text).
  - Render: with no override, the ordered values are the code tools then the registered tool, with the code tools checked. With a stored list holding a stale entry, the stale entry comes last, checked, and its label ends with `(unresolved)`; no other label does. `save`, `restore` and `confirm_empty` render. No `restore` renders without an override. The `cortex` server, even with a stored `tools` row, renders no `tools[]`, no `restore`, no `confirm_empty` and no form. A tool-only row created through the editor renders the code instructions (`from_code`) beside the overridden tools.
  - Save and restore: a save stores the list; restore removes a tool-only row and, on a row with published instructions, nulls `tools` and keeps the instructions. An empty selection without `confirm_empty` is refused with the stored list unchanged (with and without an existing row). With `confirm_empty` it stores `[]`.
  - Order: stored `['b','a']` with a, b, c ticked stores `['b','a','c']`, and with b, c ticked stores `['b','c']`.
  - Also: a ticked stale entry is refused with the list kept; the `cortex` server gets a `server` error on save and restore; an unknown server answers 404.
  - Authorization: under `NoUpdateToolsMcpInstructionPolicy` a signed-in user's tool addition and restore get 403 and the stored list is unchanged. The same viewer sees the served tools read-only, with no `tools[]`, `save` or `restore`.
- Seen failing first: the render tests failed (no checkboxes) and the save tests failed (no route) before the code.
- `tests/Feature/Ui/StylesTest.php` passes.
- Results:
  - `composer test:unit`: 636 of 637 pass. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium.
  - `composer lint:check`, `composer analyse` and `composer test:types` (100%) pass.

### Deviations

- Two files outside the group's list: `src/Atrium/CortexPlugin.php` for the routes (the listed `src/Atrium/routes.php` does not exist) and `lang/en/cortex.php` for the new strings.
- With no tool override the checklist pre-checks the server's code tools: what it serves now, as the concrete agent tool editor does. AC 19's "checked when in the stored list" governs once a list is stored. Saving untouched therefore stores the code tools as an override.
- A validation error on a ticked entry is keyed by its index in saved order (stored order first), not by its checkbox position. The editor shows it in the card's alert.

## Group 8

Renamed tables, docs and quality gate (AC 20, 21, 22, D-056). Cortex commit `a08199e` on `ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`, built against the local siblings (`composer local:install`).

### Files

- `tests/Modes/RenamedTables/RenamedTablesTest.php`: two new tests (see Tests).
- `tests/Feature/DocsTest.php` (new): the AC 21 docs test (D-040). It never spells the old namespace, so the group 12 allowlist scan is unchanged.
- `README.md`:
  - The intro, the Authorization section (an `updateTools` pointer), the Dashboard summary, the API table (`PUT` and `DELETE /cortex/servers/{server}/tools`, and "a tool override stays" on the instructions delete), the publication-cache list and the MCP tool count (29 to 31) are updated.
  - Server Instruction Overrides now names `Domains\ConcreteServer\Support\Server` and `HasCortexOverrides` (`HasVersionedInstructions` is the same trait under its old name), and lists the six deprecated old names.
  - New "Overriding a Concrete Server's Tools" section, with five subsections:
    - the API, MCP, dashboard and code entry points, plus the four resource fields;
    - "What gets served": names and code-wins ambiguity, the order promise, `[]` versus null, the flat `ToolSearch` list, the dynamic-tools caveat (validation, payload and checklist read the reflected `$tools`), gating, removed tools, stale and failing entries with `warning_ttl`, the `cortex` server, management tools not registered, no versioning;
    - "Who may change a server's tools": `updateTools`, guests allowed by default, the privilege note and trust boundary (D-033), the boot warning once per worker process, the deny-by-default snippet with `return false`, and that a host policy without `updateTools` denies;
    - "Deleting instructions keeps the tool override": 204/text or 200/resource (D-057), per-model version deletes, one Keen `mcp_instruction.deleted` entry with the per-version deletes as model events only (D-056), and at-least-once delete events;
    - "Caching and revocation": the per-node store, the revocation delay (up to `cortex.cache.fresh` on Redis; until the next successful forget after a failed one), and that the lock is unverified off SQLite;
    - "Upgrading from the McpServer domain": publish and migrate or copy only the tools migration, restart workers, missing-column fallback, rollback drops the tools, renamed classes, deprecated aliases and `composer dump-autoload -o`, old policy keys, the unshimmed-class break (drain queues, re-register listeners), and the morph map keys.
- `CHANGELOG.md`, Unreleased:
  - New first section "Upgrade notes (security-relevant)":
    - the new `PUT`/`DELETE …/tools` routes and MCP tools are live on upgrade under the guest-allowing `updateTools` default, with the boot warning;
    - a host policy without `updateTools` denies;
    - publish with `vendor:publish` and run `migrate`, or copy just the file;
    - restart workers, and rolling back drops the tools;
    - `composer dump-autoload -o`;
    - drain queues and re-register listeners.
  - Breaking: the rename with the morph-map requirement, the unshimmed classes, and the changed `DELETE …/instructions` (both responses, per-version events, one Keen entry).
  - New Deprecated section: the six shims and the old policy keys.
  - Added: the tool override, `cortex.mcp.warning_ttl`, and `defaultTools()`/`supportsToolOverrides()`.
- `config/cortex.php`: the MCP Servers banner mentions the tool override and that `cortex` cannot be overridden, and the `updateTools` policies entry points to the README section. The `warning_ttl` banner and the `updateTools` snippet from groups 3 and 4 are unchanged.
- `resources/boost/skills/cortex-development/SKILL.md`:
  - the description and the server paragraph use the new namespace and note the deprecated old names;
  - a new paragraph covers the tool override (`update-server-tools-tool`, `clear-server-tools-tool`, `updateTools` guest warning, migration);
  - the API list gets the tools routes and the delete-instructions behaviour;
  - the tool count goes to 31;
  - a new anti-pattern says not to leave `updateTools` open or register Cortex's management tools.
- `docs/agent/standards/cortex/domains.md` (new on the branch, see Deviations):
  - the domain list names `ConcreteServer`, which kept its `McpInstruction*`/`McpServer*` stems;
  - the top level adds `Support\WarningLimiter` and `Support\AiToolAsMcpTool`;
  - "Stored names" says the two ConcreteServer models keep their pre-rename names as morph aliases, the other models are stored under their own class names, and only the six shimmed names are aliased.

### Tests

- `RenamedTablesTest` (crm_ prefix on every table):
  - "saves, serves and restores a concrete server tool override on renamed tables" (AC 20):
    - the `tools` column exists on `crm_mcp_instructions`;
    - `PUT` by route name stores `['echo-cortex-tool', 'renamed-echo']` in `crm_mcp_instructions` and answers them in order with `tools_overridden` true;
    - the MCP test client lists exactly those two and calls the registered tool, and the removed code tool fails with `Tool [echo-mcp-tool] not found.`;
    - `DELETE …/tools` answers 204 and removes the tool-only row, and the client lists the code tools again and calls one.
  - "keeps a tool override when the instructions are deleted on renamed tables": a published version plus tools, then `DELETE …/instructions` answers 200 with `tools_overridden` true. `crm_mcp_instruction_versions` is empty, the pointer is null, `tools` is kept and still served.
- `DocsTest` (15 with datasets):
  - README: the update and clear routes are registered with URI `cortex/servers/{server}/tools` and methods `PUT`/`DELETE`, and the README names `PUT /cortex/servers/{server}/tools`, `DELETE /cortex/servers/{server}/tools` and both route names.
  - README: both MCP tools by the `name()` they are served under, which contains `update-server-tools`/`clear-server-tools`.
  - README: `updateTools`, and a fenced PHP block with `function updateTools(` and `return false`.
  - Boost skill: `update-server-tools` and `clear-server-tools`.
  - CHANGELOG Unreleased (up to the next `## [`): `dump-autoload -o`, `vendor:publish`, `migrate`, `updateTools` and `restart`. Its first `###` section names `updateTools` and `/cortex/servers/{server}/tools`, so the security note stays at the top.
  - `config/cortex.php`: the file's `mcp.warning_ttl` is 3600, and the comment block before the key holds the `MCP Warning Rate Limit` banner with `warning_ttl` and 3600. The block before `'policies' =>` names `updateTools` and guests and holds the deny-by-default snippet.
  - `domains.md` names `` `ConcreteServer` ``.
- Seen failing first: 12 of the 15 DocsTest cases failed before the docs changed. The three that passed checked group 3 and 4 config text that already existed. Both RenamedTables tests passed from the start, because groups 3 to 7 built the behaviour. They add the renamed-table coverage AC 20 asks for.
- Results:
  - `composer test`: `dump-autoload`, `analyse` (PHPStan, 0 errors), `lint:check` (Pint) and `test:types` (100.0 %) pass.
  - `test:unit` is 653 of 654. The one failure is the accepted D-054 ArchTest "preset → security" on the symlinked Atrium (`SearchRegistry.php:270`).
  - `tests/ArchTest.php` is unchanged, so no exclusions were added (AC 22 process note).

### Deviations

- `docs/agent/` is not tracked on this branch. In the main checkout it is untracked: the agent-os → docs/agent migration has not been committed yet. The branch still carries `agent-os/standards/backend/domain-modules.md`, which says `McpServer`.
  - The group owns `docs/agent/standards/cortex/domains.md`, and the docs test reads it from the package root. So I added it to the branch as the main checkout's copy with the ConcreteServer edits.
  - Copying the same content into the main checkout's untracked `docs/agent/standards/cortex/domains.md` was refused by the runner, so that copy still lists `McpServer`. When `docs/agent/` is committed on main, take the branch's version of this file, or the two will conflict as an add/add.
  - `agent-os/standards/backend/domain-modules.md` is outside the file list and on its way out, so I left it unchanged.
- MCP tool names: the README and the skill use the served names `update-server-tools-tool`/`clear-server-tools-tool` (group 6's convention), which contain the spec's `update-server-tools`/`clear-server-tools`. The docs test derives the names from the tool classes.
- `domains.md` "Stored names" said every moved model keeps `RefactorCircus\Cortex\Models\{Entity}` as its morph alias. No provider but `ConcreteServerServiceProvider` calls `keepMorphAliases()`, and `ModelsTest` asserts that the other six models store their own class names. The line now says what the code does.
- `docs/policies.md` (no owner in this spec) still names the old `Domains\McpServer` policy classes and does not mention `updateTools`. It resolves through the shims. A follow-up should update it.
- The README states the revocation delay from the current cache behaviour: the next request on non-Redis stores, up to `cortex.cache.fresh` on Redis, and until the next successful forget after a failed one. The spec leaves the TTL itself as an open question, and this does not settle it.

## Rework round 1

- Finding (blocker, gate): `composer test` fails on the ArchTest "preset → security", which flags `unserialize` in Atrium's `SearchRegistry.php:270`.
- No code change. This is the failure D-054 accepts, and D-054 is final: keep `tests/ArchTest.php` as is, rely on CI, and accept this one failure in local runs. AC 22 (D-040) also rules out new ArchTest exclusions. The finding's other fix, changing Atrium, is in another repository and outside this job.
- What I checked:
  - Main fails the same way: `vendor/bin/pest tests/ArchTest.php` in the main checkout gives the same single failure.
  - Cause: Pest's presets scan every PSR-4 root whose real path is outside `vendor/`. `composer local:install` symlinks `vendor/refactor-circus/atrium` to `../Atrium`, so Atrium counts as this package's code. In CI, Atrium is installed into `vendor/` and is skipped. The call already restricts classes (`['allowed_classes' => [SearchResult::class]]`).
  - I tried `->ignoring(['RefactorCircus\Atrium', …])` on the presets: the arch suite passed with it and failed without it. I reverted it because of D-054, and nothing is committed.
- Results on the branch, unchanged:
  - `test:unit`: 653 of 654 pass. The one failure is the D-054 failure. A run with the experimental ignore passed 654 of 654.
  - `lint:check` (Pint) and `analyse` (PHPStan, 0 errors) pass.
- For the gate: as long as D-054 stands, local runs keep this failure. Either the gate accepts it, or Atrium avoids `unserialize`, for example by caching search results as JSON. The Atrium change goes in Atrium's own repository.

## Rework round 2

- Finding (blocker, gate): the same ArchTest "preset → security" failure as round 1. It flags `unserialize` in Atrium's `SearchRegistry.php:270`.
- No code change again, for the same reasons:
  - D-054 is final: keep `tests/ArchTest.php` as is and accept this one local failure.
  - AC 22 (D-040) rules out new ArchTest exclusions.
  - Changing `SearchRegistry` belongs in Atrium's own repository. Its last change there was the namespace move (`2e7dd17`), so the failure comes from the local symlink, not from anything recent in Atrium.
- `test:unit` result: 653 of 654 pass. The one failure is the D-054 failure.
- The round loop does not converge: the gate counts the D-054 failure as a blocker, and the rework round cannot fix it under D-054. I returned this as a decision for the owner. The options are: the gate accepts D-054 failures, Atrium drops `unserialize` in a separate Atrium job, or the gate runs with Atrium installed from VCS. This is not a request to reopen D-054.
- Commit: an empty commit records the round. No files in the worktree changed.

## Rework round 1 (ringside check)

- Finding (blocker, gate): `composer exec ringside check` failed with "Generated files are stale or missing".
- Fix: ran `composer exec ringside sync` in the worktree. The only stale file was `AGENTS.md`, which was missing the generated `<!-- ring-master:start -->` Ring Master block. Sync added it, and the check now passes.
- Results: `ringside check` passes. `composer test:unit` passes 654 of 654.
- Commit: `f63ef9b`.
