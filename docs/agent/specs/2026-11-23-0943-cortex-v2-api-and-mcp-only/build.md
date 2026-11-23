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
