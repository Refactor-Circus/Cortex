VERDICT: PASS

Gate (worktree /Users/jay/Projects/packages/Cortex--2026-10-23-1917-virtual-and-concrete-mcp-servers):

- `composer test`: phpstan passed (0 errors), pint passed, pest passed (654 tests, 2499 assertions).
- `composer lint:check`: pint passed.
- `composer exec ringside check`: passed.

## Criteria to proof (file-level mapping; the full suite is green)

1. Folder move and provider: src/Domains/ConcreteServer/ConcreteServerServiceProvider.php, src/Domains/DomainServiceProvider.php; tests/Feature/Compat/OldNamespaceReferencesTest.php.
2. Unchanged names and old-namespace allowlist: tests/Feature/Compat/OldNamespaceReferencesTest.php, Http/McpInstructionsTest.php, Mcp/ServerInstructionToolsTest.php.
3. Morph aliases and boot warning: tests/Feature/Compat/MorphAliasesTest.php, tests/Feature/Compat/KeenInCiTest.php.
4. Written morph string: tests/Feature/Compat/MorphAliasesTest.php.
5. Gate for old morph string: tests/Feature/Compat/MorphAliasesTest.php.
6. Class shims, subprocess and unshimmed warning: tests/Feature/Compat/ClassShimsTest.php, tests/Fixtures/old-namespace-child.php, src/Domains/ConcreteServer/shims.php.
7. Old policy keys: tests/Feature/Compat/OldPolicyKeysTest.php.
8. Column, fallback and idempotent migration: tests/Feature/ToolsMigrationTest.php, tests/Feature/McpToolOverridesTest.php.
9. Update action, validation, race, ability, guests: tests/Feature/ConcreteServerToolsActionsTest.php, ConcreteServerToolsRaceTest.php, Http/ConcreteServerToolsTest.php, Mcp/ServerToolsToolsTest.php, PolicyTest.php.
9a. Guest boot warning: tests/Feature/GuestToolsWarningTest.php.
10, 10a to 10f. Serving, order, flat list, ambiguity, memo, visibility: tests/Feature/Mcp/ServerToolOverrideTest.php, ServerToolOverrideWarningsTest.php, ServerContextPinTest.php, Compat/OldNamespaceToolOverrideTest.php.
11. Delete semantics: tests/Feature/DeleteMcpInstructionActionTest.php, Http/McpInstructionsTest.php.
12. Cortex server non-overridable: tests/Feature/McpServerRegistryTest.php, Unit/McpServerRegistryDefaultToolsTest.php, Ui/ServerToolsEditorTest.php.
13. Clear action: tests/Feature/ConcreteServerToolsActionsTest.php, ConcreteServerToolsRaceTest.php.
14. Reachability and parity: tests/Feature/Http/ConcreteServerToolsTest.php, Mcp/ServerToolsToolsTest.php, Mcp/OutputSchemaTest.php.
15. Resource payload and tool-only rows: tests/Feature/ToolOnlyRowTest.php, Http/ConcreteServerToolsTest.php.
16. Stale/failing entries and limiter: tests/Feature/Mcp/ServerToolOverrideWarningsTest.php, WarningLimiterTest.php.
17. Cache after commit: tests/Feature/ConcreteServerToolsCacheTest.php.
18. Corrupt value: tests/Feature/Mcp/ServerToolOverrideWarningsTest.php, McpToolOverridesTest.php.
19. Atrium editor: tests/Feature/Ui/ServerToolsEditorTest.php, Ui/StylesTest.php.
20. Renamed tables: tests/Modes/RenamedTables/RenamedTablesTest.php.
21. Docs: tests/Feature/DocsTest.php (README, CHANGELOG, config/cortex.php, Boost skill, domains.md all in the diff).
22. Gate: results above; .github/workflows/tests.yml and composer.json changed for Keen and dump-autoload.

Mapping is by file, not by individual test name; I did not re-verify per-test coverage beyond the green suite.

## Findings

None.

## Changed files

`git diff --name-only main...ring-master/2026-10-23-1917-virtual-and-concrete-mcp-servers` lists about 190 files: the src/Domains/ConcreteServer tree, deletions in src/Domains/McpServer, CHANGELOG.md, README.md, composer.json, config/cortex.php, the new migration, views, tests and fixtures, and workbench servers.
