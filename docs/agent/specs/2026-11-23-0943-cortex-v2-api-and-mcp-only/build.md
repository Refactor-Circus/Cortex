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
