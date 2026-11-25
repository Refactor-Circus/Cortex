Risk: high

## What changed
- Atrium UI removed: `src/Atrium/`, all Blade views, Atrium/PennantPlus/WithoutAtrium tests and test modes, the workbench test host, the `ui` and `atrium` config sections and the Atrium standard.
- `composer.json` drops Atrium and PennantPlus; `dedoc/scramble` is added to require-dev. The CI `without-atrium` job is gone.
- Three new read endpoints in the VirtualAgent domain, each with an action, events, request, resource and MCP tool: `cortex.search.index`, `cortex.runnable-agents.index`, `cortex.settings-summary.show`.
- Committed `openapi.json` (Scramble export, v2.0.0) and a `client/` TypeScript package (`@refactor-circus/cortex-client`) with a generated `schema.d.ts`. New composer scripts `openapi:export` and `client:generate`.
- README, CHANGELOG (v2.0.0 upgrade notes), Boost skill and AGENTS.md rewritten for JSON API + MCP.

## Why
Cortex v2.0.0 ships the JSON API and MCP server only. Atrium v2 rebuilds the screens on Cortex's API. The three things the Blade UI computed in PHP with no route (agent search, runnable agents, settings summary) become endpoints and MCP tools (D-003 to D-007, D-006 places them in VirtualAgent). The API stays on by default (D-001). The client is TypeScript source, not published (D-010). Staleness of `openapi.json` and the client is caught by Pest tests, with no Node in CI (D-009). Keen stays in require-dev for the history tests (D-011).

## Look at
- `src/Domains/VirtualAgent/Actions/SearchAgentsAction.php:41`: per-result policy filtering, LIKE-wildcard escaping, the 5+5 limits and the single-query override fetch.
- `src/Domains/VirtualAgent/Http/Requests/SearchAgentsRequest.php:22`: the `viewAny` precondition (401 for a guest, 403 for a signed-in user).
- `src/Domains/VirtualAgent/Actions/ListRunnableAgentsAction.php`: the `run` ability filter, unpaginated.
- `composer.json` and `config/cortex.php`: breaking removals (Atrium, PennantPlus, `ui` and `atrium` keys) and the new scripts.
- `tests/Feature/OpenApiTest.php`: the export determinism and hash checks, which other jobs depend on.

## Tested
Round-2 gate: PASS. `composer test` ran phpstan, pint and pest (719 tests, 2612 assertions) and `composer lint:check` passed.
- AC 1-2: `tests/Feature/Http/SearchAgentsTest.php`, `tests/Feature/SearchAgentsActionsTest.php`
- AC 3: `tests/Feature/Http/RunnableAgentsTest.php`, `tests/Feature/ListRunnableAgentsActionsTest.php`
- AC 4: `tests/Feature/Http/SettingsSummaryTest.php`, `tests/Feature/ShowSettingsSummaryActionsTest.php`
- AC 5: `tests/ArchTest.php`, `tests/Feature/EventsTest.php`
- AC 6: `tests/Feature/Mcp/AgentDirectoryToolsTest.php` (parity), `tests/Feature/Mcp/OutputSchemaTest.php`
- AC 7: `tests/Feature/RedirectDomainsTest.php`
- AC 8-12: `tests/Feature/NoAtriumTest.php`, `tests/Feature/ComposerTest.php`, `tests/ArchTest.php`
- AC 13-16: `tests/Feature/OpenApiTest.php`
- AC 17-19: `tests/Feature/DocsTest.php`
- AC 20: `composer test`

## Risks
- Breaking for hosts: the dashboard, `cortex::ui.*` views, the `cortex-views` publish tag, `CortexSupportFeature` and the `atrium.cortex.*` routes are gone. Published config keys `ui` and `atrium` are ignored.
- Scramble schemas may be loose, because rules and responses are inferred. The ACs require only routes and methods. Tightening them is follow-up work.
- npm cannot install a subfolder of a git repo, so how Atrium consumes `client/` is left to the Atrium spec.
- The OpenAPI staleness test depends on a deterministic Scramble export, which the workbench config pins.
- Left open: no findings remain (nits: 0). No other packages are broken.
