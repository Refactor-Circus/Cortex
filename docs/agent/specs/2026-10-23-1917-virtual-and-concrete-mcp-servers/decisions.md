# Decisions

## D-001: Create the GitHub issue as drafted

- **Question:** Create the GitHub issue as drafted in ticket.md (virtual servers in the DB + tool-list overrides for class-based servers)?
- **Choice:** Create as drafted
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** ticket

## D-002: What a virtual server can expose (follow-up spec)

- **Question:** What can a virtual server expose?
- **Choice:** Registered tools plus virtual/concrete agents as tools
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** ticket

## D-003: Existing McpServer domain becomes ConcreteServer

- **Question:** What happens to the existing McpServer domain for concrete servers?
- **Choice:** Rename to ConcreteServer (keep stored names and morph aliases) and add a VirtualServer domain
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** ticket

## D-004: Scope of the McpServer → ConcreteServer rename

- **Question:** What does the McpServer → ConcreteServer rename change beyond PHP classes and namespaces?
- **Choice:** Classes and namespaces only; table keys, HTTP routes/names, MCP tool names, config keys and registered server names stay (no breaking change)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** shape

## D-005: Storage of a concrete server's tool override

- **Question:** Where is a concrete server's tool override stored?
- **Choice:** New nullable tools column on the existing mcp_instructions override row (new migration), mirroring ConcreteAgentOverrideModel
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** shape

## D-006: Standards for this spec

- **Question:** Use the standards listed in shape.md's Standards section for this spec?
- **Choice:** Use the list as proposed
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** shape

## D-007: How virtual servers are served over MCP (follow-up spec)

- **Question:** How are virtual servers served over MCP?
- **Choice:** One config-gated route with the server slug in the path (cortex.mcp.virtual.enabled, off by default), resolved per request
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** shape

## D-008: Where a virtual server's tool and agent lists live (follow-up spec)

- **Question:** Where do a virtual server's tool and agent lists live?
- **Choice:** On the server row, like virtual agents (tools json, concrete_agents json, pivot to virtual agents); only instructions are versioned
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** shape

## D-009: Policy ability for the concrete server tool override

- **Question:** Which policy ability guards the concrete server tool override, and what does the bundled policy grant?
- **Choice:** Dedicated updateTools ability, bundled policy allows it (like every other Cortex ability)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** spec-review

## D-010: MCP error for an unpublished virtual server (follow-up spec)

- **Question:** What MCP error does a client get for a virtual server with no published version?
- **Choice:** A distinct message from VirtualServerNotPublishedException
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** spec-review

## D-011: Host servers on the old namespace after the rename

- **Question:** Do host-app servers extending the old Domains\McpServer\Support\Server or using Concerns\HasVersionedInstructions keep working after the rename?
- **Choice:** Keep class_alias shims at the old FQCNs with a deprecation note (removed next major), tested
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** spec-review

## D-012: No public #[LockedServerTools] attribute

- **Question:** Is a public #[LockedServerTools] attribute part of this spec?
- **Choice:** Drop it; only the cortex server is non-overridable, as a registry rule
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** spec-review

## D-013: Split into two specs

- **Question:** Ship as one spec, or split into (1) rename + concrete tool override and (2) virtual servers?
- **Choice:** Two specs, rename + concrete override first
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** spec-review

## D-014: Serve-time name collision on a virtual server (follow-up spec)

- **Question:** What happens at serve time when a registered tool and an agent on a virtual server resolve to the same MCP tool name (e.g. a tool registered after the server was saved)?
- **Choice:** Tool wins, warning logged
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** spec-review

## D-015: Virtual server route with empty middleware (follow-up spec)

- **Question:** What does the virtual server MCP route do when enabled with an empty cortex.mcp.virtual.middleware?
- **Choice:** Fail closed — don't register the route (and say why) until middleware is non-empty
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-23
- **Raised by:** spec-review

## D-016: Stale references when serving

- **Question:** How does serving handle stale references (deleted/unpublished/unregistered tools or agents) in a virtual server's lists or a concrete server's tool override?
- **Choice:** Skip the entry, log a warning, serve the rest (and report a missing list on the resource)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-017: Host policies keyed by the old McpServer model FQCNs

- **Question:** How are host apps handled whose config/cortex.php policies map is keyed by the old McpServer model FQCNs (now silently ignored, falling back to the allow-all bundled policy)?
- **Choice:** ConcreteServerServiceProvider remaps old policy keys to the new classes and logs a deprecation warning (class aliases for the two models and two policies), tested
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-018: Deleting instructions when the row also holds a tool override

- **Question:** What does DELETE servers/{server}/instructions (and delete-server-instructions) do when the override row also holds a tool override?
- **Choice:** Clear instructions only (delete versions, null the published version); remove the row only when tools is null; a separate action clears the tool override
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-019: Public names of the clear-tools action

- **Question:** What are the name, HTTP route, MCP tool name, ability and events of the separate action that clears a concrete server's tool override (restore code tools, D-018)?
- **Choice:** ClearConcreteServerToolsAction; DELETE cortex/servers/{server}/tools (cortex.servers.tools.destroy); MCP tool clear-server-tools; ability updateTools; ConcreteServerTools{Clearing,Cleared}ActionEvent
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-020: Compatibility shims stay in this spec

- **Question:** Do the model/policy shims and the policy-key remap (D-011, D-017) stay in this spec, or move to their own compatibility spec?
- **Choice:** Keep them in this spec, listed in the Goal
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-021: Override entry matching a code tool and a registered tool

- **Question:** An override entry matching both a code-declared tool and a registered Cortex tool — keep code-wins, or reject it at validation?
- **Choice:** Keep code-wins, log a warning at serve time
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-022: Override entry whose tool throws while being built

- **Question:** When a stored override entry resolves but its tool throws while being built, does serving skip it or fail the server?
- **Choice:** Skip, log a warning, serve the rest (as D-016)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-023: Run mode of the rollback-cache and unique-race tests

- **Question:** How do the rollback-cache test (AC 17) and the unique-race test (AC 9) run, given RefreshDatabase wraps each test in a transaction?
- **Choice:** DatabaseMigrations (no wrapping transaction) on file-based SQLite for these two tests
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-024: Migration test shape

- **Question:** Is the migration test (AC 22) a staged migration (migrate to the step before, insert, migrate, roll back)?
- **Choice:** Staged migration test with pre-insert and rollback
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-025: How the order test observes the ServerContext tools

- **Question:** How does the order test (AC 10a) observe the ServerContext tools?
- **Choice:** Reflection helper shared with the vendor pin test
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-026: Server context memoized per request

- **Question:** Is the server context built once per request (memoized) so warning counts are deterministic?
- **Choice:** Memoize per request and assert exactly one warning per entry
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-027: Lazy compatibility shims

- **Question:** Should the compatibility shims be eager (class_alias file in autoload.files) or lazy (an autoload fallback registered for the old Domains\McpServer\ prefix)?
- **Choice:** Lazy spl_autoload_register fallback in the same autoload.files file, aliasing only when an old name is requested
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-028: ServerContext rebuild failure at runtime

- **Question:** What should serving do when rebuilding the ServerContext fails (a laravel/mcp internals change, or an exception from the rebuild itself)?
- **Choice:** Log a warning and serve the code-declared tools unchanged
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-029: Versions deleted through models

- **Question:** Should the delete action remove versions through models (firing model events and audit entries) or through a bulk query?
- **Choice:** Per-model deletes, firing {Entity}Deleted events and audit entries
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-030: Surfacing the guest-allowed updateTools default on upgrade

- **Question:** How should the guest-allowed default of updateTools be handled for existing installs on upgrade?
- **Choice:** Keep the default and add a one-time boot warning when updateTools is allowed to guests in a non-local environment
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-031: Database drivers for the race and migration tests

- **Question:** Which database drivers must the concurrency (unique race) and migration tests run on in CI?
- **Choice:** SQLite only (drop the conditional second-driver wording from AC 9)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-032: Cortex server screen hides the tool editor

- **Question:** On the cortex server's Atrium screen, should the tool editor be hidden, or shown read-only with an explanation?
- **Choice:** Hide the editor and the restore button for non-overridable servers
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-033: Tool update authorized at the server level only

- **Question:** Should the tool update also check authorization per added tool, beyond the server-level updateTools ability?
- **Choice:** Server-level updateTools only, documented as a trust boundary (per-tool ability as a follow-up)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-034: Missing tools column degrades with a warning

- **Question:** How should an upgraded host that has not run the new migration behave when serving?
- **Choice:** Degrade - treat a missing tools column as no override and log one warning
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-035: Warnings rate-limited through the cache

- **Question:** How should the stale, ambiguous and corrupt-value warnings be deduplicated across requests?
- **Choice:** Cache-based rate limit per server+entry with a TTL
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-036: Stored tool order is a product promise

- **Question:** Is the stored tool order a product promise, or only an internal property of the server context?
- **Choice:** Promise - assert order on the resource tools field plus the context via reflection
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-037: Testable guest boot warning

- **Question:** How is the once-per-process guest boot warning (D-030) made testable?
- **Choice:** Resettable once-per-process flag plus a method that re-runs the check, called from the booted callback
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-038: Autoload shim guaranteed in CI

- **Question:** How does CI guarantee the autoload shim file is active for the subprocess test (AC 6)?
- **Choice:** Test asserts vendor/composer/autoload_files.php contains the shim, and CI runs composer dump-autoload
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-039: Guest boot warning with mixed per-server results

- **Question:** How should the guest boot warning (AC 9) treat a policy that allows updateTools to guests for some servers and denies it for others?
- **Choice:** Log once if any overridable server is allowed for guests, naming those servers
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-040: How the docs test and the manual review steps are checked

- **Question:** How should the docs test and the manual review steps (AC 2 import-only diff, AC 3 Keen not skipped, AC 22 no ArchTest exclusions) be checked?
- **Choice:** Docs test checks route, tool and ability names and the snippet only; Keen skip and AC 2 baseline become machine checks; exclusion-list review becomes a process note
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-041: CI change is in scope

- **Question:** Should the CI change (refactor-circus/keen for the Keen-conditional tests, and a composer dump-autoload step) be an in-scope deliverable?
- **Choice:** In scope: add keen to require-dev and a dump-autoload CI step
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-042: Idempotent tools migration, copied alone

- **Question:** How should hosts that already published Cortex migrations get the new tools column without duplicate-migration failures?
- **Choice:** Make the new migration idempotent and document copying just that file
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-043: Narrow compatibility layer, documented break

- **Question:** Should the compatibility layer alias more of the old Domains\McpServer namespace than Support\Server, the trait, the two models and the two policies (events, actions, Data, exceptions)?
- **Choice:** Keep the narrow set and document the break in the CHANGELOG and README (drain queues, re-register listeners)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-044: Warning rate-limit TTL is a config key

- **Question:** What is the TTL of the cache-based warning rate limit (D-035), and is it fixed or configurable?
- **Choice:** Config key under cortex (new config shape)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-045: Warning TTL key and default

- **Question:** What are the config key name and default for the warning rate-limit TTL (D-044 settled that it is a config key, but not its name or value)?
- **Choice:** `cortex.mcp.warning_ttl`, default 3600 seconds (sits beside `cortex.mcp.servers`; int cast and hard default on every read)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-046: Bundled updateTools default unchanged

- **Question:** Should the bundled updateTools default stay guest-allowing on upgrade, or should a config switch gate the tools PUT/DELETE routes and MCP tools until a host opts in?
- **Choice:** Keep D-009/D-030 as is, unless you accept a new config key; then default false
- **Rationale:** None given (answered in RingMaster). Applied as: this answer adds no config key, so D-009/D-030 stand (guest-allowed default plus boot warning); a gating key, if ever added, defaults to false.
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-047: Rebuild-failure warning uses the D-035 limiter

- **Question:** How should the rebuild-failure warning (D-028) be rate-limited?
- **Choice:** Reuse the D-035 cache limiter keyed by server plus exception class
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-048: Clear "moved to" message for unshimmed old names

- **Question:** Should unshimmed old-namespace names (events, actions, Data, exceptions) fail with a clear 'moved to ...' message from the lazy autoload fallback, or as a plain class-not-found?
- **Choice:** Fallback logs a clear 'moved to ...' message without aliasing (does not reopen D-043)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-049: Row lock on both delete/clear and update

- **Question:** Should the update action also take a row lock (lockForUpdate re-read) so the race protection holds on MySQL/Postgres?
- **Choice:** Lock on both sides and document that non-SQLite behaviour is untested
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-050: Hardening layers stay in this spec

- **Question:** Should the hardening layers (per-request memo, cache rate limit, missing-column fallback, rebuild fallback, guest boot warning, subprocess autoload test) all stay in this spec?
- **Choice:** Keep all in this spec (D-026, D-028, D-030, D-034, D-035, D-038 already settle each layer) and drop the 'open question' wording
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-051: Served-order test asserts tools/list order

- **Question:** Should the served-order test also assert the order a real MCP client receives in tools/list?
- **Choice:** Assert tools/list order too, after confirming laravel/mcp preserves it
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** spec-review

## D-052: Keystone looks up the ConcreteServer services

- **Question:** Keystone's CortexIntegration imports Cortex's old `Domains\McpServer\Services\McpServerRegistry` and `McpInstructionOverrides`, so after the ConcreteServer rename every MCP call through CortexServer and every Keystone-based package server fails with "Target class does not exist". These two services are not in D-043's shim set and AC 1 moves the whole folder. How should Keystone keep working?
- **Choice:** Change Keystone's CortexIntegration to look up `Domains\ConcreteServer\Services\...`, falling back to the old names via `class_exists`, release Keystone, then raise Cortex's keystone constraint (this job waits for the Keystone change, or uses `composer local:install` meanwhile)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** build

## D-053: Test env pinned in phpunit.xml

- **Question:** The runner session leaks `SESSION_DRIVER=database`, `CACHE_STORE=database`, `APP_ENV=local` and `DB_CONNECTION=sqlite` into PHP; Testbench reads them, so ~60 Atrium/UI tests fail with "no such table: sessions". How should the test environment be fixed?
- **Choice:** Pin the test env in `phpunit.xml(.dist)` with `<env name=... value=... force="true"/>` for `SESSION_DRIVER=array`, `CACHE_STORE=array`, `APP_ENV=testing` (in this job's group 1)
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** build

## D-054: Local ArchTest security-preset failure on symlinked Atrium

- **Question:** After composer local:install, the ArchTest security preset scans Atrium's code (../Atrium is symlinked) and flags unserialize in Atrium/src/Domains/Search/Services/SearchRegistry.php:270; main fails the same way locally, CI installs Atrium from VCS into vendor/ where the preset does not look. How should local runs get to all green for this and later groups?
- **Choice:** Treat it as a local-environment failure: keep tests/ArchTest.php as is, rely on CI, and accept this one failure in local runs
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-24
- **Raised by:** build

## D-055: A throwing missing-column check serves code tools for that call

- **Question:** AC 8 leaves open what serving does when the missing-column check itself throws (for example the schema query fails because the connection is down). Right now the exception propagates out of ToolsColumn::exists() and McpToolOverrides::for(), so tools/list fails, and nothing is memoized. Which behaviour should ship?
- **Choice:** Treat a throwing check as 'no tool override' for that call only: serve the code tools with the instructions override still applied, log a rate-limited warning (context server plus the exception message, limiter subject 'column-check:<exception class>'), and do not memoize, so the next request checks again
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-25
- **Raised by:** build

## D-056: One Keen audit entry per instructions delete

- **Question:** AC 11 says each version deleted through the model writes its own Keen audit entry, but Keen only records action events (its AuditRecorder listens to ActionStartingEvent/ActionFinishedEvent), so per-version model deletes produce no entries. What should the delete action do about audit entries?
- **Choice:** Accept one Keen audit entry per delete (mcp_instruction.deleted): the per-version deletes show only as model events. Note it in the README and CHANGELOG (group 8).
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-25
- **Raised by:** build

## D-057: Response of the instructions delete

- **Question:** What should DELETE cortex/servers/{server}/instructions and delete-server-instructions-tool answer? AC 11 says the response is the updated server resource showing tools_overridden true, but the spec's Risks section calls the exact response an open question. Today the route answers 204 and the tool answers text, and the existing tests assert both, which AC 2 says must stay unchanged.
- **Choice:** Answer 204 and text whenever the row is removed, as today. When the row survives because it still holds a tool override, answer 200 with McpInstructionResource, and the tool answers that resource as structured content.
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-25
- **Raised by:** build

## D-058: Clear the D-054 gate failure by fixing Atrium

- **Question:** The gate keeps counting the ArchTest security-preset failure as a blocker. That failure is the local failure D-054 accepts, and no rework round can clear it without breaking D-054 or AC 22, so the rounds will not converge. How should the gate stop flagging it? (This does not reopen D-054; it asks how the gate should act on it.)
- **Choice:** Fix Atrium in a separate Atrium job: stop using unserialize in SearchRegistry (for example, pass search results as JSON), then run `composer local:update` here
- **Rationale:** None given (answered in RingMaster).
- **Date:** 2026-10-25
- **Raised by:** build
