# Decisions

## D-001: Does the JSON API stay enabled by default (`cortex.routes.enabled`)?
- Choice: Yes. `cortex.routes.enabled` stays `true` and `keepRoutesEnabled()` stays, so existing API consumers and Atrium v2 keep working on upgrade. The README keeps its warning to add auth middleware.
- Rationale: The spec removes a UI; flipping the API off would be a second, unrelated breaking change. Changing the default belongs in its own spec.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-002: What does the stats endpoint return?
- Choice: There is no stats endpoint. `CortexPlugin` registers no widgets, so no aggregate was computed for a dashboard widget. The counts the settings panel showed are in `settings-summary`.
- Rationale: Shape lists stats as a general rule "where the package had widgets"; Cortex had none. Adding an endpoint nobody consumes widens the API surface for nothing.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-003: Search payload and limits
- Choice: `GET cortex/search?q=` (`cortex.search.index`), `q` required string max 255. Up to 5 virtual agents (name or slug `LIKE`, wildcards escaped, ordered by name) then up to 5 concrete agents (case-insensitive substring of the registered name, ordered by name). Items `{type: virtual_agent|concrete_agent, key, name}`. Authorization as the Atrium search did: `viewAny` on either model to call, each half by its `viewAny`, each result by `view`.
- Rationale: Mirrors `CortexPlugin::search()` exactly, minus UI-only parts (URLs, translated group labels), so Atrium v2 gets the same results and no record leaks past its policy. Escaping wildcards is the safe reading of "contains".
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-004: Runnable agents payload
- Choice: `GET cortex/runnable-agents` (`cortex.runnable-agents.index`), unpaginated `data` list in the search item shape; virtual agents by name then concrete agents by name, each filtered by `run`; call refused unless `viewAny` on either model.
- Rationale: Mirrors `RunnableAgents::for()` and `CortexPlugin::mayViewAgents()`. `type` + `key` replace the `virtual:{slug}` option strings, since the client posts to `virtual-agents.run` or `concrete-agents.run` itself.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-005: Settings summary payload and authorization
- Choice: `GET cortex/settings-summary` (`cortex.settings-summary.show`) answers `{providers: [{name, models}], tool_count, agent_count, server_count, cache_enabled}` from the same sources as `CortexPlugin::settings()`. No policy is asked, as for `providers.index`, `tools.index` and `servers.index`; the route middleware is the gate.
- Rationale: There is no model to ask a policy of, and every value is already readable through those unauthorized endpoints. Inventing an ability on an unrelated model would be arbitrary.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-006: Where do the cross-domain endpoints live?
- Choice: In the VirtualAgent domain (actions, requests, resources, controllers, events, MCP tools; routes in `VirtualAgent/routes.php`), next to `providers.index`, which is already a package-wide read there.
- Rationale: The routes standard needs a domain `routes.php`; a new domain is an architectural step this spec does not need. The `cortex/domains` standard already gives VirtualAgent "runtime and providers".
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-007: Do the new endpoints get MCP tools?
- Choice: Yes: `SearchAgentsTool`, `ListRunnableAgentsTool`, `ShowSettingsSummaryTool`, with parity tests.
- Rationale: The local standard `cortex/actions` says a new operation gets both an HTTP request and an MCP request on the same action; following it avoids a standards-feedback row.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-008: Do cross-package references flagged in shaping become specs here?
- Choice: No. They stay out of scope: Keystone's hook removal and Atrium's integration already have their own specs, and no other spec is opened from this one.
- Rationale: A spec changes only its own package; the user asked for out-of-scope treatment.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-009: How is staleness of `openapi.json` and `client/` detected?
- Choice: A Pest test re-runs the Scramble export through Testbench into a temp file and compares it with `openapi.json`, checks the document's operations against the registered `cortex.*` routes, and checks the sha256 header that `client/scripts/generate.mjs` writes on the first line of `client/src/schema.d.ts`. No CI Node job.
- Rationale: Keeps `composer test` free of a Node toolchain while still failing on any drift between routes, OpenAPI and client.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-010: How is the client packaged?
- Choice: TypeScript source only (`exports`/`types` → `src/index.ts`), no build, not published to npm, version `2.0.0`. Atrium consumes it from the v2 tag; how (pnpm subfolder git dependency or otherwise) is the Atrium spec's call.
- Rationale: Fewest moving parts; Nuxt compiles TS source. npm cannot install a subfolder of a git repo, which is noted as a risk.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-011: Keen and PennantPlus mentions
- Choice: PennantPlus goes entirely (`require-dev`, `suggest`, repository, PennantPlus mode tests). Keen stays in `require-dev` and its repository, because the history route and tool tests need it; the config's Auditing banner stops naming it (shape: "the config loses its mentions of Keen and PennantPlus").
- Rationale: Removes what only the Atrium feature flag needed, keeps the test coverage of a surface that stays.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-012: Translations
- Choice: Keep `lang/en/cortex.php` and `loadTranslationsFrom()`, trimmed to keys referenced from `src/` (today `redirect_domain_invalid`); the `cortex-lang` publish tag stays.
- Rationale: The redirect-domain validation message still uses it; UI labels belong to Atrium now.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-013: CHANGELOG release section
- Choice: Add a `## [v2.0.0]` section (compare `v0.1.0...v2.0.0`) with the upgrade notes; tagging is done at release time, outside this spec.
- Rationale: The task asks for a v2.0.0 upgrade note; the DocsTest pattern already parses release sections.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-014: Spec review
- Choice: `/ring-master-spec-review` skipped; `meta.yml` set to `status: approved` directly.
- Rationale: The user instructed the spec to be written and approved without review or questions.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping
