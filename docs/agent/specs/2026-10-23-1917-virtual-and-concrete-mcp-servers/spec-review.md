# Spec review

## Round 1

Changed in spec.md (non-architectural fixes only):

- AC 2, 3: dropped the "only namespace changes" clause (not testable); the morph test now asserts `Relation::getMorphedModel()` and loads stored rows holding the old class strings.
- AC 5: a dedicated `updateTools` ability with HTTP and MCP policy tests and a docs note on guest-allowing policies, to stop instruction editors escalating. The default the policy grants stays open.
- AC 6, 19: tests name the Laravel MCP test client with `tools/list` and `tools/call` assertions; agent tools are tested with the AI fake, `prompt` validation, a length cap and failures mapped to MCP tool errors.
- AC 7: testable wording (insert a row directly, assert code defaults); dropped "LockedTools-style". Whether the attribute belongs in this spec stays open.
- AC 8, new 8a: row-sharing rules (only the delete action removes the row; clearing either field keeps the other; tool-only rows resolve to code instructions) to prevent data loss, and the `cortex` server cannot have its tools overridden, with tests.
- AC 10, 20: observable cache tests (fresh read, rollback leaves cache intact, key absent when unpublished, gone after publish, update and delete); virtual server cache keyed by id so a slug change leaves no stale key.
- AC 14: next version computed under a lock or retried, so concurrent creates do not 500.
- AC 15: defined the MCP tool name function, format validation and every collision pairing at write time.
- AC 18: tests for `false`, `null`, `'true'`, `true`, plus a pre-build spike for the parameterized route, per-request resolution, the 404 and `route:cache`.
- AC 21: cycle fixture inserted directly in the database.
- AC 22, 24, 25: per-screen feature tests; a docs test plus a review checklist; the CHANGELOG no longer claims "no breaking change" and follows the shim decision; ArchTest covers the new domains with no exclusions; migrations reversible with null defaults.
- Out of scope: added overriding the `cortex` server, per-server access control, rate or cost limits, extra auth, and import/export.

Left untouched on purpose, as open questions for the user: class alias shims for the old namespace, whether `#[LockedServerTools]` is in this spec, the serve-time name collision winner, one spec versus a split, behaviour with empty virtual middleware, stale reference handling at serve time, the default ability for the tool override, and the unpublished-server MCP error payload. They remain in Risks and open questions.

### Round 1, revision of spec.md after the scope, risk and testability findings

Changed in spec.md, and why:

- Renumbered the criteria in reading order (scope: 3a after 3c, 3d only in an addendum, 7 a placeholder, 10b before 10a). Old to new: 1 to 1, 2 to 2, 3 to 3, 3b to 4, 3c to 5, 3a to 6, 3d (D-017 addendum) to 7, 4 to 8, 5 to 9, 6 to 10 (6a to 10d map to 10a to 10d), 8 to 11, 8a to 12, 8b to 13, 9 to 14, 10 to 15, 10a to 16, 10b to 17 (corrupt-value part split out to 18), 11 to 19, 12 to 20, 13 to 21, 14 to 22. Old 7 is dropped. The Goal now lists deliverables by the new numbers, including the policy-key remap, the guest-exposure docs and the deny-by-default test.
- Inlined the decided answers D-019 to D-024 into the criteria (scope): the clear action's route, MCP tool, ability (`updateTools`, shared) and events in AC 13 and 14 and the Events section; ambiguity (D-021) in AC 9 and 10d; throwing tool (D-022) in AC 16 and the resource contract in AC 15; run mode (D-023) in AC 9 and 17; staged migration (D-024) in AC 22. Removed the "working name" and "open question" wording for those, and folded the D-017 and D-018 addenda into AC 7 and 11. A short "Decision notes" section keeps the reasoning.
- AC 3 to 5 (testability): the morph map entries are stated exactly (key = old FQCN, value = new class), so `getMorphClass()` returns the old string and `getMorphedModel(old)` returns the new class, asserted together. The Gate test now loads a row from a stored old morph string instead of using an aliased instance.
- AC 6 (testability): the subprocess test names `PHP_BINARY`, the package's `vendor/autoload.php`, a child that never boots Laravel, and asserts exit code and a stdout marker. The `autoload.files` assertions and the `composer dump-autoload` note are marked "eager form" so the criterion stays valid under either answer to the open question.
- AC 9 (scope, testability, risk): the race test is SQLite-only until the driver question is answered; the "another driver" clause is gone. Guest exposure now also covers the policy docblock, and states that registry membership is the opt-in and that code-declared tools are selectable only if the server declares them.
- AC 10a, 10d, 16 (testability): the order test requires an assertion on the served context without choosing how it is observed; warning counts are asserted per direct context-building call, the resource computation does not log, and no per-client-call count is asserted.
- AC 11 (risk): a test shows `DELETE` on a row with tools returns the server resource with `tools_overridden` true; the README migration note joins the CHANGELOG note. The models-versus-bulk deletion choice is stated as open.
- AC 12 (testability): the `cortex` ignore-test names `CortexServer`, its registered route and the MCP test client.
- AC 17, 18 (scope, testability, risk): 10b was split. Caching (AC 17) names the `array` store and `DatabaseMigrations`, adds an after-commit ordering test beside fresh-read and rollback tests, and requires a shared cache store in the README. The corrupt-value handling is its own AC 18.
- AC 19 (testability, risk): the checklist contents and order, marking of unresolved entries, assertions, and an explicit confirmation required to save an empty selection.
- AC 21, 22 (testability): docs checks are all machine-checked with concrete strings; the migration is located by glob and the SQLite column-drop dependency is noted.
- Out of scope and Risks (scope, risk): added ordering beyond stored order and no reorder UI, no bulk toggle, concurrent edits between editors, no tool-change auditing beyond Keen's model saves, the permanent old morph strings (a data migration is needed when the shims go), the empty-override risk and the per-node cache store risk.

Left untouched on purpose (open questions, now referenced from the criteria through `questions.yml`): eager versus lazy shims; behaviour when rebuilding the `ServerContext` fails at runtime (the laravel/mcp version constraint finding belongs with it); versions deleted through models versus a bulk query; how the guest-allowed `updateTools` default is surfaced on upgrade; which database drivers run the race and migration tests; how the order test observes the context; whether the context is built once per request.

New architectural questions: none.

## Round 2 (scope)

Decisions D-009 to D-016 are now folded into spec.md; no open questions remain in it. Changed:

- Title, goal and header: the spec is now rename + concrete tool override only (D-013); decisions range D-001 to D-016.
- Removed the former virtual server ACs (11-21), their screens/docs/gate parts, the virtual domain design, config block, events, migration `…000001` and virtual-only out-of-scope items. They are preserved below for the follow-up spec; spec.md has a short "Follow-up spec" pointer.
- Added AC 3a (D-011 shims with a host-server test) and removed the "old FQCNs are not kept" line and the shim risk.
- AC 5: policy default settled by D-009 (bundled policy allows `updateTools`).
- AC 7 dropped (D-012; number kept as a marker); `LockedServerTools` removed from Design and the registry rule is `cortex` only; AC 8a extended with a test that a directly inserted row for `cortex` is ignored when serving.
- AC 10 now includes `missing_tools`; new AC 10a for stale entries (D-016) with its test.
- Renumbered the remaining screens/docs/gate criteria to 11-14 and scoped them to the concrete override (editor test, RenamedTables flow, docs test, ArchTest).
- Removed the stale "open question" wording and the old Risks bullets on collisions and agent tools (virtual-only).

Remaining architectural questions: none.

## Round 2 (risk)

Checked against `src/Domains/McpServer`, `PublicationCache`, laravel/mcp `ServerContext`, `ToolRegistry`. Changed in spec.md:

- AC 1, 3a: the shims cannot autoload from PSR-4 once the folder is gone; they live in a `composer.json` `autoload.files` file.
- AC 3: new rows keep writing the old morph string (the alias), asserted.
- AC 5: one name-resolution rule (code `name()` first, then registry name), 422 on resolved-name duplicates, empty list valid and distinct from null, unknown server 404 / `Not found.`, race-safe row creation (`server` is unique).
- AC 6: removed tools are not callable; tool-only rows (null `published_version_id`) are read. `McpInstructionOverrides` filters on published rows, so it cannot be reused.
- Design: `McpToolOverrides` selects all rows with non-null `tools`; every changing action and the delete action forget `mcpToolsKey()` after commit; serving derives code tools from the parent `createContext()` (dynamic tools survive), `ToolSearch` grouping is flattened when overridden.
- Risks: host `policies` config keyed by the old model FQCN is silently ignored.

Open questions for the user (not decided here):

1. Old model FQCNs in published config: see decisions raised in the round 2 reply (policy keys; delete semantics).
2. AC 8 contradiction: delete is "the only action that removes the row" yet "clearing instructions keeps `tools`". Needs a decision on what `DELETE servers/{server}/instructions` / `delete-server-instructions` does when `tools` is set.

## Round 2 (testability)

Changed in spec.md (non-architectural only):

- AC 3a: the shim test also checks `class_exists()` and the resolved class, and that a tool override applies to a host server on the old names.
- AC 5: the "race-safe" claim now has a deterministic test (a `creating` listener inserts a competing row once).
- AC 6: served tools keep the stored order, asserted.
- AC 8: noted that its tests depend on the open delete-semantics question; the test asserts the row and the served list either way.
- AC 9: parity defined (same state and payload, `Parity::gaps()`); rule tests live at the action, HTTP/MCP test only mapping (test-layers standard).
- AC 10: rollback test method specified (failure inside the transaction, cache warmed first).
- AC 10a: warning asserted via the log spy with server name and entry.
- AC 14: migration test covers pre-existing row, null default and rollback.
- Design: the vendor pin test says what it asserts; new "Test plan" (unit, action, serving layers, fixtures, which tests keep old strings).
- AC 3 / policy-key config handling (risk question 1): no AC depends on it yet; once decided it needs a test that a config entry keyed by the old FQCN does what the answer says.

New architectural questions: none.

### Round 1, second pass: scope, risk and testability findings

Changed in spec.md (open questions left untouched; where a fix touches one, the criterion states the behaviour that holds either way and names the question):

- Goal: now lists every deliverable (shims, policy-key remap, tool override with update and clear actions, resource reporting, changed `DELETE` semantics). Why: scope critics found deliverables that the Goal did not mention.
- AC numbering: AC 7 is now a one-line "dropped" note; addenda are cross-referenced from numbered ACs (addendum D-017 is counted as AC 3d, D-018 is owned by AC 8 and 8b). AC 9 covers the update and clear actions together.
- New AC 8b: clear-override action with behaviour, input, events pattern, cache forget, row removal rule, 404/422 cases and tests. The public names (route, MCP tool, ability, event classes) are deliberately left to the open question; the criteria hold for any name.
- Out of scope: added class stem renames, non-tool overrides, versioned or audited tool override, MCP resources and prompts, shim-removal migration path.
- AC 10 split into 10 (resource payload, plus new `ambiguous_tools`) and 10b (caching, rollback, non-array stored value read as null with a logged warning).
- AC 3 split into 3 (morph resolution), 3b (written morph string), 3c (Gate policy lookup through the old alias); Keen-dependent audit assertions are conditional on Keen being installed.
- AC 3a: added composer.json `autoload.files` assertion, `@deprecated` docblock assertion, and a subprocess test for the load-order claim; CHANGELOG upgrade note about `composer dump-autoload`.
- AC 5: race test asserts only the observable result (no 500, one row, saved list) and no longer prescribes a retry location or SQLSTATE; guest-exposure documentation plus deny-by-default snippet and a denying-policy test across HTTP, MCP and Atrium (D-009 unchanged).
- AC 6: sub-criteria 6a to 6d (order asserted on the `ServerContext` array, flat list, host-added tools, code-vs-registry ambiguity logged and reported; a registry tool added after the override was saved does not swap the code tool).
- AC 8: states the changed `DELETE` semantics in the CHANGELOG and adds a test for an override created by a prior flow.
- AC 10a: fixed the log contract (one warning per unresolved entry per context build, keys `server` and `entry`).
- AC 13: machine-checked docs assertions (README names, CHANGELOG text, `@deprecated`, `domains.md`) and a named manual checklist for the rest.
- AC 14: removed the "or asserts the column default" branch; one assertion set for the migration test.
- Migration note: the follow-up migration must not depend on the `tools` column.
- `decisions.md`, `shape.md`, `standards.md`: virtual-server decisions (D-002, D-007, D-008, D-010, D-014, D-015) and standards marked as follow-up spec material.

Findings left as open questions, not decided here: whether the shims and policy-key remap stay in this spec; the public names of the clear action; rejecting ambiguous entries at validation; whether the bundled policy still allows guests `updateTools`; skip versus fail when a resolved tool throws while being built (the "wrap and skip" fix is not specified in the spec); the run mode of the rollback-cache and race tests; the staged migration test mechanics.

New architectural questions: none.

### Round 1, third pass: scope, risk and testability findings

Changed in spec.md (the eight open questions are untouched; where a fix borders one, the text says it is open and states what holds either way):

- Goal: added bullets for per-request context memoization (D-026), the rebuild-failure fallback (D-028), the guest boot warning (D-030, marked scope-open) and tool-only rows; each bullet names its AC. Added a short paragraph bounding scope. Why: deliverables had no Goal bullet.
- Header: decisions range corrected to D-001 to D-031.
- AC 2: the regression baseline is the moved server tests with import-only diffs. Why: otherwise "names unchanged" is unchecked.
- AC 3: morph aliases use the merging `Relation::morphMap()`; test that a later host entry keeps both keys; README says host morph maps must keep the keys and not use `enforceMorphMap()` carelessly (docs only). Keen tests must run in CI (`require-dev` or one matrix job). Why: risk and silently skipped tests.
- AC 6: precondition that `vendor/composer/autoload_files.php` lists the shim file; `dump-autoload -o` note; classmap-authoritative limitation stated (also in Risks). CI mechanism left open.
- AC 8: states that migrations are published only, adds the publish-and-migrate upgrade note; runtime behaviour on a missing column left open.
- AC 9: documented the privilege path of a server-level `updateTools`; per-tool ability left open. Guest warning: robustness rules that hold either way (never throws, skipped in console, same ability arguments); hook, dedupe state and testability left open. Removed the "once per process" wording because the dedupe scope is open.
- AC 10a: also asserts the resource `tools` order (public observable); whether order is a promise stays open.
- AC 11 and 13: removal decided from a `lockForUpdate()` re-read inside the transaction, with race tests; Out of scope narrowed accordingly.
- AC 12: cortex screen presentation left open; resource reports `tools_overridden` false for it.
- AC 15: servers without a row report no override; tests that tool-only rows behave in list, show, version list, publish and the Atrium screen.
- AC 16 and 18: `entry` is the stored entry name; the corrupt-scalar warning uses `server` and `value_type` instead. Cross-request dedupe left open.
- AC 17: probe defined (`DB::transactionLevel()` at each forget must be 0, at least one forget seen, zero forgets and the old value on rollback).
- AC 19: fixed form field and button names (`tools[]`, `confirm_empty`, `save`, `restore`), unresolved marker, and ordered checkbox values for the order assertion.
- AC 21: literal heading and sentences the docs test greps for; migration publish note added.
- AC 22: the ArchTest exclusion check is a review step, not a test; the SQLite assumption is stated.
- Out of scope: `ListCortexHistoryTool` unaffected, orphan rows, cortex screen presentation, per-tool ability.
- Risks: missing migration on upgrade, classmap limitation, privilege note.
- `standards.md`: lists which rows apply and which are follow-up only. `decisions.md`: D-023, D-024, D-025 and D-031 AC cross-references updated to the current numbering (AC 17, 9, 22, 10a, 9).

Not changed: the open questions listed in the task (cortex screen editor, guest warning scope, per-tool authorization, missing-column behaviour, warning dedupe, tool order promise, guest warning testability, CI autoload guarantee).

New architectural questions: none.

## Carried to the follow-up spec (virtual servers)

Former ACs and design, updated for D-010, D-014, D-015 and D-016. Decisions D-002, D-007, D-008, D-010, D-014, D-015 apply.

- Domain `VirtualServer`: `VirtualServerModel` (`virtual_servers`: ULID id, `name`, unique `slug`, nullable `description`, FK-free nullable `published_version_id`, `tools` json default `[]`, `concrete_agents` json default `[]`, timestamps), `VirtualServerVersionModel` (`virtual_server_versions`: ULID id, `virtual_server_id` FK cascade, integer `version`, `content` text, timestamps, unique pair), `virtual_server_agents` pivot (both FKs cascade, unique pair). Keys in `Tables::DEFAULTS` and `config/cortex.php`; policies for both models in `cortex.policies`; model events for every hook. Versions are immutable. Version policy defers to its server (`view` reads, `update` adds or publishes).
- Actions with starting/finished events and one transaction each: Create, Update (partial with `Missing`), Delete, List (paginated), Show; version Create (next number under a row lock or retried on unique violation), List, Show, Publish (`firstOrFail()`).
- Input: `name`, `slug` (unique by model class), `description`, `tools` (registered, distinct), `virtual_agents` (existing slugs), `concrete_agents` (registered names). MCP tool name of an entry is the tool's `name()`, the agent slug or the registered name, must match the MCP tool-name format; same-name entries (tool/agent, virtual/concrete agent) fail 422 on the later field.
- HTTP `cortex/virtual-servers` (`cortex.virtual-servers.index|store|show|update|destroy`, `…versions.index|store|show|publish`) and MCP tools (`list|create|show|update|delete-virtual-server`, `list|create|show|publish-virtual-server-version`) with `outputSchema()` and HTTP/MCP parity tests (lists also with empty `data`). `VirtualServerResource` and `VirtualServerVersionResource` with `schema()`; resource reports missing entries per list (D-016).
- Serving: `VirtualMcpServer` (extends `Laravel\Mcp\Server`, reads the `{server}` route parameter, `firstOrFail`, `VirtualServerFactory` builds tools: registered tools via `ToolRegistry`/`AiToolAsMcpTool`, virtual agents via `AgentFactory::make()`, concrete agents via `AgentRegistry::make()`, each wrapped by `Support\AgentAsMcpTool` with one required `prompt` string, max length in schema). Tests with the Laravel MCP test client and the AI fake; missing/empty `prompt` fails validation; agent runtime failure is an MCP tool error; a cyclic sub-agent graph fails with `CircularAgentReferenceException` (cycle inserted directly in the DB).
- Route: only when `cortex.mcp.virtual.enabled === true` (tests for `false`, `null`, `'true'`, `true`) and, per D-015, `cortex.mcp.virtual.middleware` is non-empty; otherwise not registered and a warning says why. Default `mcp/virtual/{server}`. Pre-build vendor spike: two slugs resolve per request, unknown slug gets a transport 404, `route:cache` works. Config banner about auth middleware and model cost.
- Errors: unknown slug answers `Not found.`; no published version fails with `VirtualServerNotPublishedException` and a distinct MCP message (D-010). Published instructions cached through `PublicationCache` keyed by server id; absent when unpublished, gone after publish, update and delete.
- Serve-time rules: tool/agent name collision, tool wins and a warning is logged (D-014); stale tools, concrete agents or pivot agents are skipped with a warning and the rest served (D-016).
- Config: `mcp.virtual = ['enabled' => false, 'route' => 'mcp/virtual', 'middleware' => []]`; `tables` and `policies` gain the new keys.
- Screens (index, create, show/edit, versions with publish, history), RenamedTables flow (create → version → publish → serve), README/config/Boost skill/CHANGELOG/`domains.md` entry, `composer test`; migration `2026_10_23_000001_create_cortex_virtual_server_tables.php`.
- Out of scope there: remote MCP proxying, resources/prompts, stdio transport, versioning tool and agent lists (D-008), per-server access control, rate or cost limits, import/export.

## Round 1

Revision of `spec.md` for the scope, risk and testability findings. The eight open questions were left alone: where a fix borders one, the text says it is open and states what holds either way.

Changed in spec.md:

- Header: one line saying the ticket, folder and `shape.md` heading cover both specs, that this spec closes only the concrete half, and that the follow-up spec will be linked by path once it exists. Why: the folder name suggests a wider scope.
- Goal: bullets rewritten so each AC sits in exactly one bullet (the old list overlapped); dropped the "tests are the bound / maps to one bullet" framing and the "large on purpose" sentence; added a "Must have versus hardening" split. Whether the hardening stays is the open question, so the split only labels. Why: the mapping claim was false and the scope looked unbounded.
- Out of scope: added non-SQLite drivers untested, no new audit or history events for tool changes, no new middleware or auth config for the new routes, no further aliases for old-namespace events, actions, Data, requests and exceptions, and other docs untouched; shortened the concurrent-edit bullet to one rule. Why: gaps and a contradiction with the in-scope races.
- AC 6: the subprocess script is a checked-in fixture (`tests/Fixtures/old-namespace-child.php`); the stale-autoloader failure is explained for developers after `composer update` or `composer local:install`. Why: the test depended on a generated script and failed with no guidance.
- AC 9 boot warning: stated the cheap-check rule (read policy and config directly, skip Gate evaluation for the bundled default), the per-worker repeat, the log contract (`updateTools` in the message, context key `servers`) and a test for the `booted` wiring. The "policy allows only some servers" outcome stays open.
- AC 10: the unknown-tool error is asserted on the test client's error response; new 10e (a selected tool keeps its `shouldRegister()` gating, Cortex's management tools are not selectable unless a host registers them) and 10f (the memo is request-scoped, with a test that an update between two requests in one process is served).
- AC 15: names the four consumers covered and says no other is; "every existing consumer" removed.
- AC 16: order of the memo and the cache limiter (memo first), tests clear the cache for the AC 10d counts, a throwing cache store falls back to logging, `Cache::add` for an atomic claim, hashed and bounded keys, the missing-column check memoized per process, plus a throwing-store test. The TTL value stays open.
- AC 19: form hook names moved to a new Design section "Atrium form hooks"; added the order test (stored `['b','a']`, tick a, b, c gives `['b','a','c']`; unticking `a` gives `['b','c']`).
- AC 21: CHANGELOG must say that old-namespace events, actions, Data, requests and exceptions move without aliases.
- Risks: added "Rename without aliases for non-shimmed classes" (silent listener and catch mismatches, unserializing queued jobs) and "Publishing the migrations tag re-stamps files" (the `publishesMigrations()` hazard). Whether to alias more, and how hosts receive the migration, remain open.

Not changed, because open questions depend on them: the CI/Keen dependency wording (AC 3), the literal-sentence docs test and manual review steps (AC 2, AC 21, AC 22), the migration upgrade wording (AC 8, AC 21), the cache TTL, the partial-policy boot warning, and the aliasing of more classes. The finding on an arch guard for `Domains\McpServer` strings in AC 2 was not applied because it turns a review step into a machine check, which is an open question.

New architectural questions: none.

### Round 1, second pass (decisions D-026 to D-044 applied)

Revision of `spec.md`, `standards.md` and `shape.md` for the scope, risk and testability findings. Open questions were left alone: where a fix borders one, the text says it is open and states what holds either way.

Changed in spec.md:

- Header: decision range now D-001 to D-044. Why: it stopped at D-038.
- Goal: added a CI bullet (keen in `require-dev`, `dump-autoload` step, no-skip guard; D-041). The hardening paragraph now cites D-026, D-028, D-030, D-034, D-035 and D-041 and says every listed AC is in scope. The "stay or move to a smaller release" sentence stays, because it is an open question; until answered the split is a build-order hint.
- AC 2: the reviewer step became a machine check (D-040): an architecture test fails when `Domains\McpServer` appears outside an allowlist of shim, morph-alias, old-row, old-policy-key and fixture files.
- AC 3: the review step for skipped Keen tests became a guard test that fails in CI (env `CI`) when the Keen model class is missing. Added a non-fatal, once-per-process boot warning when an old morph key stops resolving, with two tests.
- AC 8: migration is idempotent (D-042) in `up()` and `down()`, tested by running it twice; rollback drops stored overrides; the CHANGELOG tells hosts to restart workers after migrating (memoized missing-column check).
- AC 9: stale "partial allow is open" replaced with D-039 (log once, naming the allowed servers), the bundled-default detection rule (resolved policy class equals the bundled class) and a partial-allow test (`servers` = `[A]`).
- AC 10: sub-criteria reordered 10a to 10f (10d ambiguity moved before 10e); labels and cross-references unchanged.
- AC 11: one race mechanism (a listener on the action's starting event writes `tools` with a query-builder update; a ran-flag is asserted). Rollback behaviour stated: audit writes roll back with the transaction, model events are at-least-once; rollback test added beside AC 17(c).
- AC 12: rule is by registered name; a host subclass of `CortexServer` under another name is overridable; unregistered-name rows are ignored and stay.
- AC 13: same race mechanism as AC 11.
- AC 16: TTL is a new `cortex` config key (D-044) with a banner, int cast and hard default; the key name and default stay an open question, so tests set the key through config and time-travel past the configured value.
- AC 17: a throwing `forget` after commit is logged and never fails the action; test added.
- AC 19 and Design: the Atrium unresolved marker is fixed to the visible label `(unresolved)`; the `data-unresolved` alternative was dropped because Atrium attribute passthrough could not be verified here.
- AC 21: docs test trimmed to route, tool and ability names and the snippet (D-040); the literal sentence greps became README requirements without a grep. Added the TTL config banner line, copy-one-file migration note (D-042), worker restart note and a security-relevant top-of-Unreleased note for the new guest-reachable routes and tools.
- AC 22: exclusion-list review is a process note; CI workflow stated; idempotent-migration test added.
- Design, Migration: `hasColumn()` guard in `up()` and `down()`.
- Out of scope and Risks: stale "more aliases is open" and "migration wording is open" replaced with D-043 and D-042. New Risks: lock semantics unproven off SQLite, at-least-once delete events, stale cache after a failed forget, host morph map override.

Changed in standards.md and shape.md: `architecture/configuration` moved to applicable (D-044); rows describing only the follow-up spec are tagged `[follow-up only]`; shape.md's Standards section says which lines are follow-up only.

Not changed, because open questions depend on them: the TTL key name and default; whether the bundled `updateTools` default stays guest-allowing or gets a kill switch (the CHANGELOG note only documents the current default); rate limiting of the rebuild-failure warning (D-028); fail-loud behaviour for unshimmed old names; a write-side `lockForUpdate` in the update action; whether the hardening layers stay in this spec; whether AC 10a also asserts the order a real client receives.

New architectural questions: none.

### Round 1, third pass (D-045 to D-051 applied; scope, risk, testability)

Changed in spec.md (the open questions were left alone; where a fix borders one, the text says it is open):

- Header: decision range D-001 to D-051 (decisions.md runs to D-051).
- Goal: qualified "without breaking host apps" (servers, models and policy config keep working through the shims; unshimmed classes are the documented break, D-043); the bullets and the hardening list now place the boot warning (AC 9a), the rebuild-warning rate limit (D-047), the update action's lock (D-049) and the unshimmed-name warning (D-048).
- AC 2: the `Domains\McpServer` allowlist is a fixed set of named files asserted by members and count. Why: prose allowlist could grow silently.
- AC 3: README says a host must not re-map the two models under another alias; the boot check also warns when `getMorphClass()` differs from the old string.
- AC 6: new bullet for D-048 (unshimmed old name logs "moved to", no alias, `class_exists()` false), fallback never throws and guards recursion and `class_alias`, test with no booted app asserts no exception; `composer test` runs `dump-autoload` first.
- AC 8: the missing-column check resolves its connection from the model; the memo stores only a completed result.
- AC 9: the update action re-reads with `lockForUpdate()` (D-049); new bullet and test for a host policy without `updateTools` (Gate denies: 403/401); the boot warning is split into its own criterion AC 9a (hardening), and boot-warning tests reset the flag in `beforeEach`.
- AC 10a: rewritten per D-051: stored order asserted in the resource, the context and the exact `tools/list` a client receives, after a vendor pin test confirms laravel/mcp preserves order; the "compared as a set" wording is gone.
- AC 10e: test for a wrapped `Laravel\Ai` tool's own eligibility and that the request context reaches its authorization.
- AC 12, 15, Out of scope: orphan rows stated once (Out of scope); Atrium reads the registry directly, no endpoint lists selectable tools; non-SQLite and "docs untouched" lines removed (settled by D-031 and AC 21). AC 15 names the untested tool-only consumers as an accepted gap.
- AC 16 and Design: rebuild-failure warning goes through the limiter keyed by server and exception class, with a test (D-047); limiter keys carry the `PublicationCache` key family prefix; TTL read clamped to at least 1 second; every warning-count test flushes the cache in `beforeEach`; the throwing store is a real `Store` driver whose call is asserted.
- AC 21: docs test greps the CHANGELOG Unreleased keywords and the `warning_ttl` key and banner; README states the revocation delay and the N-delete cost.
- Design, Migration: reserved `…000001` slot dropped.
- Risks: N-delete cost of clearing instructions; tool-only rows untested consumers.

Not applied, because an open question depends on them: the fallback when the column check throws (and not memoizing it); the cache entry's TTL and revocation delay (README states it, value open); the response of `DELETE …/instructions` (CHANGELOG states previous and new, value open); where the autoload fallback logs with no booted app; whether a test verifies `lockForUpdate()` (no SQL-level assertion added); the seam that fakes console and web for the boot warning; what a ticked unresolved entry does in the Atrium form. Not applied for lack of a verifiable source: naming Keen's audit model class and column for the AC 3/4 tests (Keen's contract is not in this repo's spec).

New architectural questions: none.

## Approved with open questions (2026-10-24)

The user approved the spec as written in RingMaster. These questions were not answered, so where they matter the spec text as it stands applies. They are not decisions: raise them again in build or review if one blocks.

- **Q-047: Should a failing `Schema::hasColumn()` check (database unreachable or wrong connection) at serve time fall back to code tools or fail the request?**
  - Options: Fall back to code tools with a rate-limited warning and do not memoize the failure; Fail the request; Treat as a missing column and memoize
  - Critic recommendation: Fall back to code tools with a rate-limited warning and do not memoize the failure
  - Evidence: AC 8 and D-034 cover a missing column (memoized per process) but not a thrown error from the check itself
- **Q-048: What is the TTL of the `mcpToolsKey()` cache entry, and is the revocation delay for a cleared tool override acceptable?**
  - Options: Use the existing PublicationCache TTL and document the delay; Add a short dedicated TTL for the tools map; No TTL (forever), rely on forget only
  - Critic recommendation: Use the existing PublicationCache TTL and document the delay in the README
  - Evidence: AC 17 says the old value stays served until the key expires or the next successful forget, but names no TTL
- **Q-049: What should DELETE …/instructions return, given it previously removed the whole override and now may keep the row?**
  - Options: Return the updated server resource always; Keep the previous status/body when the row is removed, and the server resource only when it is kept; Return 204 in both cases
  - Critic recommendation: Keep the previous status/body when the row is removed, and the server resource only when the row is kept, so existing clients don't break
  - Evidence: AC 11 gives the response for a surviving row but not what existing callers see when the row is removed
- **Q-050: Where may the lazy autoload shim log the D-048 'moved to' message when the app is not booted?**
  - Options: Only when the app container and logger are resolvable, otherwise silent; Always via error_log; Never log, only document
  - Critic recommendation: Only when the app container and logger are resolvable, otherwise silent
  - Evidence: AC 6 and D-048 have the autoload fallback log; autoloaders run before the container exists in CLI and composer scripts
- **Q-051: Should the lockForUpdate re-read in the update, delete and clear actions be verified by a test, given SQLite ignores the lock?**
  - Options: Add a SQL-grammar assertion (the re-read compiled on the MySQL grammar contains 'for update'); Keep it documented and untested; Add an opt-in MySQL CI job that runs the race tests
  - Critic recommendation: Add the grammar assertion; cheap, deterministic, and makes AC 11, 13 and D-049 checkable without a second driver
  - Evidence: spec.md AC 11, AC 13, Risks 'Lock semantics are unproven off SQLite'; D-031 limits drivers to SQLite, D-049 requires the lock
- **Q-052: How is 'not running in the console' and 'non-local web environment' faked for the guest boot warning (AC 9)?**
  - Options: The check method takes an optional environment/console resolver argument defaulting to the app's real values; Tests swap the app's environment and call the check through a subclass or a protected resolver method; Cover the console and local conditions with a single boot-level test through an Artisan command
  - Critic recommendation: Give the public check method an injectable resolver (or read both values from container bindings tests can override); keeps D-037's single public method and makes the four conditions deterministic
  - Evidence: spec.md AC 9 'Upgrade surfacing'; D-037 settles the flag and method but not the seam for console/environment
- **Q-053: What does a ticked unresolved entry in the Atrium form do on save?**
  - Options: Drop it silently and store the remaining list; Refuse the save with a field error naming the entry; Keep it and store it as-is (bypassing the AC 9 validation)
  - Critic recommendation: Refuse with a field error; matches AC 9 (unknown entries 422 on tools.N) and keeps one validation rule across surfaces
  - Evidence: spec.md AC 19 shows unresolved entries as ticked-capable checkboxes, AC 9 rejects unknown entries
