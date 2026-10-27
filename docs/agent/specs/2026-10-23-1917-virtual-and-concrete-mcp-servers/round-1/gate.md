VERDICT: CHANGES

## Gate results (worktree /Users/jay/Projects/packages/Cortex--2026-10-23-1917-virtual-and-concrete-mcp-servers)

- `composer test`: PASS (phpstan 0 errors, pint passed, pest 654/654, 2499 assertions).
- `composer lint:check`: PASS (pint).
- `composer exec ringside check`: FAIL (exit 1). Output: `ringside: 1 problem(s) - Generated files are stale or missing. Run: vendor/bin/ringside sync`.

## Blocker

1. Ringside check fails because generated files are stale or missing. Run `ringside sync` (via `composer exec ringside sync`), commit the regenerated files and rerun the check. Note that composer also warns the ringside plugin is not in allow-plugins.

## Acceptance criteria mapping (by test file, from the changed-file list; names not individually verified)

Mapping is at file level. Per-test names were not enumerated.

- AC 1, 2: tests/Feature/Compat/OldNamespaceReferencesTest.php, tests/Feature/Http/McpInstructionsTest.php, tests/Feature/Mcp/ServerInstructionToolsTest.php; src/Domains/ConcreteServer/ConcreteServerServiceProvider.php, DomainServiceProvider.php
- AC 3, 4, 5: tests/Feature/Compat/MorphAliasesTest.php, tests/Feature/Compat/KeenInCiTest.php
- AC 6: tests/Feature/Compat/ClassShimsTest.php, tests/Fixtures/old-namespace-child.php, src/Domains/ConcreteServer/shims.php
- AC 7: tests/Feature/Compat/OldPolicyKeysTest.php
- AC 8, 22 (migration): tests/Feature/ToolsMigrationTest.php, tests/Feature/McpToolOverridesTest.php
- AC 9, 9a: tests/Feature/ConcreteServerToolsActionsTest.php, tests/Feature/ConcreteServerToolsRaceTest.php, tests/Feature/PolicyTest.php, tests/Feature/GuestToolsWarningTest.php
- AC 10, 10a-10f, 16, 18: tests/Feature/Mcp/ServerToolOverrideTest.php, ServerToolOverrideWarningsTest.php, ServerContextPinTest.php, tests/Feature/WarningLimiterTest.php
- AC 11, 13: tests/Feature/DeleteMcpInstructionActionTest.php, tests/Feature/ConcreteServerToolsActionsTest.php, tests/Feature/ConcreteServerToolsRaceTest.php
- AC 12: tests/Feature/McpServerRegistryTest.php, tests/Unit/McpServerRegistryDefaultToolsTest.php, tests/Feature/Ui/ServerToolsEditorTest.php
- AC 14: tests/Feature/Http/ConcreteServerToolsTest.php, tests/Feature/Mcp/ServerToolsToolsTest.php
- AC 15: tests/Feature/ToolOnlyRowTest.php, tests/Feature/Http/ConcreteServerToolsTest.php, tests/Feature/Mcp/OutputSchemaTest.php
- AC 17: tests/Feature/ConcreteServerToolsCacheTest.php, tests/Feature/PublicationCacheTest.php
- AC 19: tests/Feature/Ui/ServerToolsEditorTest.php, tests/Feature/Ui/ScreenControlsTest.php
- AC 20: tests/Modes/RenamedTables/RenamedTablesTest.php
- AC 21: tests/Feature/DocsTest.php; README.md, CHANGELOG.md, config/cortex.php, resources/boost/skills/cortex-development/SKILL.md, docs/agent/standards/cortex/domains.md
- AC 22: `composer test` green; CI workflow .github/workflows/tests.yml changed

## changed_files

140+ files, see `git diff --name-only main...ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers`. Includes src/Domains/ConcreteServer/** (new), src/Domains/McpServer/** (removed or reduced), tests, fixtures, README.md, CHANGELOG.md, composer.json, config/cortex.php, migration 2026_10_23_000002_add_tools_to_cortex_mcp_instructions.php, workbench files.
