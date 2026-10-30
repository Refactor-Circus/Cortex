# Register Roster redirect domains

## Goal

Cortex owns the MCP redirect-domain UI for any owner model, placed on other packages' screens only through editable defaults in `config/cortex.php`. By default these are Roster's organization tab and user aside, which replaces the copy Roster renders today. Cortex also declares every one of its own Atrium screens with the standard slots and tab bar. It stops using Atrium's removed history components, and it suggests Atrium instead of requiring it.

## Acceptance criteria

### Redirect domains on other packages' screens

1. **Config.** `config/cortex.php` gains `atrium.redirect_domains.screens` under the existing `atrium` section, with a banner. It defaults to `['roster.organization' => 'tab', 'roster.user' => 'aside']`. Keys are screen keys; a value is `'tab'` or the name of a slot. Proven by a config test that reads the default. No Cortex PHP code names `roster` or a Roster class: a test greps `src/` for `roster` (case-insensitive) and finds nothing outside comments.
2. **Registration.** While Atrium is installed and `cortex.ui.enabled` is true, Cortex registers, from `boot()`, one entry per config entry:
   - for `'tab'`: `Atrium::screens()->tab($target, TabDefinition::make('cortex.mcp')->label(__('cortex::cortex.mcp'))->icon('globe-alt')...)`
   - for any other value: `Atrium::screens()->section($target, $slot, SectionDefinition::make('cortex.redirect-domains')->label(__('cortex::cortex.redirect_domains'))...)`

   An empty list registers nothing. A unit-level feature test reads `ScreenExtensions::tabs('roster.organization')` and `sections('roster.user', 'aside')` for the defaults. It also asserts that a custom list (`['acme.account' => 'before']`) registers only that entry, and that `cortex.ui.enabled = false` registers nothing.
3. **Visibility.** The tab and the section are visible (through `authorize()`, never `permission()` or `public()`) only when all of these hold. A feature test covers each case being false on its own, for both the tab and the section:
   - `RedirectDomains::enabled()` is true.
   - The record is a saved Eloquent model (`$record instanceof Model && $record->exists`).
   - Cortex's Atrium plugin is authorized for the request: `PluginRegistry::authorized($request)` has the key `cortex`, so its features and its routes are on.
   - The viewer passes `viewAny` on `RedirectDomainModel` with the owner, through Cortex's `ScreenAccess::allowsFor($request->user(), 'viewAny', RedirectDomainModel::class, [$record])`.
4. **Owner.** The owner is the screen's record (the row, inside a row slot). The resolver returns `['domains' => ListRedirectDomainsAction` for that owner, `'ownerType' => $record->getMorphClass()`, `'ownerId' => (string) $record->getKey()]`. The forms post `owner_type` and `owner_id`, and the controller resolves them with the existing `RedirectDomains::owner()`. Proven by the feature tests in criterion 6, which use workbench models with no morph alias (FQCN type) and one with a morph alias.
5. **Rendering.** The tab panel and the section render one view, `cortex::ui.screens.redirect-domains`, built from `x-atrium::*` only. It renders:
   - the hint (`cortex::cortex.owned_redirect_domains_hint`)
   - an add form when the viewer passes `create` with the owner. It has `data-testid="add-redirect-domain"` and posts to `atrium.cortex.redirect-domains.store` with hidden `owner_type`/`owner_id`.
   - a table of the owner's domains (`data-testid="owned-redirect-domains"`), with a remove button (`data-testid="remove-redirect-domain"`) on each row the viewer may `delete`
   - `x-atrium::empty-state` when the owner has none

   `StylesTest` covers the new view.
6. **Moved behaviour (ported from Roster's `tests/Cortex/RedirectDomainsTest.php`).** `tests/Feature/Ui/OwnedRedirectDomainsTest.php` runs against the workbench test host (criterion 8) and covers:
   - **Organization tab:** `GET` the host `roster.organization` screen with `?tab=cortex.mcp` shows the panel. Posting `domain=claude.ai` with the owner stores `https://claude.ai` for that owner and redirects back to the host page (`->from($hostUrl)`). The domain is then listed, and removing it deletes it.
   - **Denied by policy:** with a fixture `RedirectDomainPolicy` that denies `viewAny` for an owner, the tab is absent and `?tab=cortex.mcp` answers 403 (Atrium criterion 7). With one that denies `create`, a POST with that owner answers 403 and stores nothing.
   - **Owner-scoped removal:** a `DELETE` naming owner A for a domain of owner B answers 404, and B's domain remains.
   - **Validation:** an invalid value redirects back with an error on `domain` and stores nothing.
   - **User aside:** the host `roster.user` screen renders the section in `aside` (`data-testid="section-cortex.redirect-domains"`), and add and remove work as above.
   - **Read-only:** when `viewAny` passes but `create` and `delete` are denied, the list shows without the add form or remove buttons.
   - **Switched off:** with `cortex.redirect_domains.enabled = false`, neither the tab nor the section renders.
7. **Controller changes.** `RedirectDomainUiController::store` and `destroy` accept optional `owner_type` and `owner_id` (validated as the JSON API's `ownerRules()` validates them):
   - **With an owner:** `store` authorizes `create` with `[$owner]` and creates for the owner. `destroy` finds the domain with `RedirectDomainModel::query()->ownedBy($owner)->whereKey($domain)->firstOrFail()` and authorizes `delete`. Both redirect to `url()->previous()` when its host is the application's own, or else to `atrium.cortex.redirect-domains.index`. An unknown owner answers 404.
   - **Without an owner:** behaviour is unchanged. The existing test `manages domains from the dashboard` passes unmodified.
   - **Tests:** a feature test covers the same-host redirect, the foreign-host fallback and the unknown owner.

   No new routes are added.
8. **Test host.** The tests use a workbench test-host screen, not Roster as a dev dependency:
   - `Workbench\App\Http\Controllers\TestHostScreenController` serves `GET test-host/{key}/{id?}` with `web` middleware, registered by the test case's `defineRoutes()`.
   - Its key is a route parameter, so one controller serves `roster.organization`, `roster.user` and `acme.account`.
   - It declares `Screen::standard($key, Screen::DETAIL)->record($model)` with a Gate-based `access()`, resolves it, and renders a workbench view with `x-atrium::screen-tabs`, `screen-tab-panel` and `screen-sections` for each standard slot, laid out per Atrium criterion 15.
   - The record is a `Workbench\App\Models\Organization` (workbench migration, loaded from `workbench/database/migrations` by the test case) for `roster.organization`, and the workbench `User` for `roster.user`.

   `composer.json` gains no Roster dependency (asserted by criterion 15's test).

### Cortex's own screens

9. **Declared screens.** Every Cortex page with a controller view declares itself with `Screen::standard()` and resolves it after its own authorization. Each has only the synthetic `overview` tab built in:

   | Key | Route (`atrium.cortex.`) | Kind | Record | Subject class for `access()` when the record is null | `row.actions` row model |
   |---|---|---|---|---|---|
   | `cortex.virtual-agents` | `virtual-agents.index` | list | none | `VirtualAgentModel` | each `VirtualAgentModel` |
   | `cortex.virtual-agent-create` | `virtual-agents.create` | detail | none | `VirtualAgentModel` | |
   | `cortex.virtual-agent` | `virtual-agents.edit` | detail | the `VirtualAgentModel` | | |
   | `cortex.concrete-agents` | `concrete-agents.index` | list | none | `ConcreteAgentOverrideModel` | `ScreenAccess::concreteAgent($name, $override)` |
   | `cortex.concrete-agent` | `concrete-agents.show` | detail | `ScreenAccess::concreteAgent($agent, $override)` | | |
   | `cortex.run` | `run` and `run.store` (both render the page) | detail | none | none (permission extensions hidden) | |
   | `cortex.tools` | `tools.index` | list | none | `ToolDescriptionModel` | `ScreenAccess::toolDescription($name, $descriptions->get($name))` |
   | `cortex.tool-description` | `tools.description` | detail | `ScreenAccess::toolDescription($tool, $description)` | | |
   | `cortex.servers` | `servers.index` | list | none | `McpInstructionModel` | `ScreenAccess::serverInstruction($name, $instructions->get($name))` |
   | `cortex.server-instructions` | `servers.instructions` | detail | `ScreenAccess::serverInstruction($server, $instruction)` | | |
   | `cortex.redirect-domains` | `redirect-domains.index` | list | none | `RedirectDomainModel` | each stored row's `RedirectDomainModel` (configured rows get no row slot) |

   A feature test with a dataset over all 11 screens registers a tab and a section in every standard slot (`header.actions`, `before`, `after`, plus `aside` on detail or `row.actions` on list screens, with at least one row present). It asserts each section renders in its slot and not in an undeclared one, and that the tab appears and opens through `?tab=`.
10. **Access closure.** Each screen's `access()` is `fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allowsFor($request->user(), $permission, $record ?? $subject)`, with `$subject` from the table. On `cortex.run`, a null record denies. Tests:
    - a `permission('view')` section on `cortex.virtual-agent` shows when the fixture policy allows and hides when it denies
    - a `permission('viewAny')` section on `cortex.virtual-agents` follows the class check
    - a `permission()` section on `cortex.run` never shows
11. **Layout.** Per Atrium criterion 15, an open registered tab's panel replaces the page content, and only `header.actions` stays. `header.actions` sections render inside `x-atrium::page-header`'s actions, after the page's own actions. `before` and `after` render around the content. `aside` renders in a right-hand column only when it has visible sections. `row.actions` renders inside each row's last cell, after its own controls. With nothing registered, the markup is unchanged: every existing feature test passes without modification, except those listed in criterion 13.

### History removal and Atrium as a suggestion

12. **No history in Cortex's UI.** The `<x-atrium::audit-trail>` calls in `ui/virtual-agents/index`, `ui/virtual-agents/form` and `ui/partials/override` are removed, and `CortexPlugin::navigation()` no longer calls `historyNavItem()`. Screens declare no audit subject (Keen attaches History by wildcard). The Keystone JSON route `GET cortex/history` and the history MCP tool are unaffected (`tests/Feature/HistoryTest.php` passes unchanged). A test asserts that the plugin's navigation has no item whose route is `atrium.history.show`. A grep of `resources`, `src`, `README.md`, `CLAUDE.md`, `docs/agent/standards` and `resources/boost` for `audit-trail` and `historyNavItem` finds nothing.
13. **Forced test changes.** Existing tests may change only where this spec's decisions force it, and the PR lists each by file and test name. The expected set is:
    - `tests/Feature/Ui/AuditTrailTest.php`: deleted (the component is gone from Atrium).
    - `tests/Feature/HistoryNavigationTest.php`, `links its own audit log from its sidebar group`: replaced by criterion 12's assertion.
    - `tests/Pest.php` and `tests/TestCase.php`: gain `ResetsScreenExtensions` and the test-host routes and migrations.
14. **Composer.** `refactor-circus/atrium` moves from `require` to `require-dev` (the suite needs it), and it is added to `suggest` with a description. Keystone stays the only suite package in `require`. A test reads `composer.json` and asserts:
    - `require` has no `refactor-circus/*` key other than `refactor-circus/keystone`
    - `suggest` has `refactor-circus/atrium`
    - `require-dev` has `refactor-circus/atrium`
15. **Arch test.** `tests/ArchTest.php` gains `arch('only the Atrium layer uses Atrium')->expect('RefactorCircus\Cortex')->not->toUse('RefactorCircus\Atrium')->ignoring('RefactorCircus\Cortex\Atrium')`, and it passes. Registration lives in `RefactorCircus\Cortex\Atrium\ScreenRegistrations` (below). `CortexServiceProvider` reaches it only behind the same string `class_exists('RefactorCircus\Atrium\Atrium')` and `cortex.ui.enabled` guard that `registerAtriumPlugin()` uses. A test asserts `composer.json` lists no `refactor-circus/roster`.
16. **No-Atrium proof.** A `WithoutAtrium` suite proves Cortex boots and serves its HTTP API and MCP without Atrium:
    - **Files:** `phpunit.without-atrium.xml`, bootstrap `tests/WithoutAtrium/bootstrap.php`, tests in `tests/WithoutAtrium`, excluded from the default `phpunit.xml.dist` suite. The bootstrap loads Composer's autoloader and removes the `RefactorCircus\Atrium\` PSR-4 prefix, so no Atrium class can load. Its `WithoutAtriumTestCase` registers no Atrium provider.
    - **Assertions:**
      - `class_exists('RefactorCircus\Atrium\Atrium')` is false
      - the app boots with `CortexServiceProvider`
      - no route name starts with `atrium.`
      - `cortex.redirect-domains.index` (JSON) answers 200 and `store` creates a domain
      - `mcpTool(ListRedirectDomainsTool::class)` answers
      - a virtual agent can be created over the JSON API
    - **Gate:** a composer script `test:without-atrium` runs it, and `composer test` calls it after `@test:unit`.
    - **CI:** `.github/workflows/tests.yml` gains a `without-atrium` job that runs `composer remove --dev refactor-circus/atrium --no-update`, installs, and runs `composer test:without-atrium`.

### Documentation

17. **Docs.** Three places change:
    - **README:** a "Redirect domains on other screens" section documents `cortex.atrium.redirect_domains.screens`, the `cortex.mcp` tab key, the `cortex.redirect-domains` section key, the visibility rules from criterion 3, and that the owner's `RedirectDomainPolicy` decides who manages the domains. It also notes that Atrium is suggested, not required.
    - **Local standard:** `docs/agent/standards/cortex/atrium-screens.md` replaces its "History" section with the screen-declaration rule: keys, standard slots, the access closure, and no audit components.
    - **Other docs and changelog:** `CLAUDE.md` and the Boost skill drop their audit-trail and history-nav text. `CHANGELOG.md` has an upgrade note covering:
      - Atrium moved to `suggest` (apps using the dashboard require it themselves)
      - the new config key
      - the `owner_type`/`owner_id` fields on the UI store and destroy routes
      - removed history UI (Keen provides it)
      - grouped merge with Atrium, Keen and Roster

    A docs test asserts the README section exists and contains `redirect_domains.screens`, `cortex.mcp` and `suggest`.
18. Done means `composer test` passes, including `test:without-atrium`. Cortex has no browser suite, so there is no `test:browser` step.

## Design

**Placement.** `RefactorCircus\Cortex\Atrium\ScreenRegistrations` (final, static `register(): void`) reads `cortex.atrium.redirect_domains.screens` and calls `Atrium::screens()->tab()` or `section()` per entry. Bad entries are not checked again: Atrium's own validation throws `InvalidArgumentException` at boot for a malformed target or slot. `CortexServiceProvider::boot()` calls it from a private `registerAtriumScreens()`, right after `registerAtriumPlugin()` and with the same guard. A target screen that nobody declares never renders (Atrium criterion 1).

**Definitions.** Tab `cortex.mcp` has the label `cortex::cortex.mcp` ("MCP") and icon `globe-alt`. Section `cortex.redirect-domains` has the label `cortex::cortex.redirect_domains`. Both use `view('cortex::ui.screens.redirect-domains')`, and they share one `authorize` closure (criterion 3) and one resolver (criterion 4). These closures run on every target screen's render, so they return early: they check `enabled()` and `$record?->exists` first and query nothing.

**Owner.** The screen's record is the owner. Its type in the form is `getMorphClass()`, so the morph map, already the registry `RedirectDomains::owner()` reads, decides the type string. Cortex never inspects which package the model belongs to (D-001, D-002 settled by the addendum).

**Authorization.** All of it is Cortex's own: `RedirectDomainPolicy` `viewAny`/`create` with the owner, and `delete` on the domain, through `cortex.policies`. These are the same checks the JSON API and MCP tools make for owned domains. The host's own authorization has already run before `resolve()` (Atrium criterion 9).

**Screen helper.** `RefactorCircus\Cortex\Atrium\Screens::declare(string $key, string $kind, ?Model $record, ?string $subject): Screen` (final, static) builds `Screen::standard()`, sets the record, and adds the access closure from criterion 10, so the 11 controllers share one definition. Controllers inject `ScreenExtensions` (Atrium's) and pass `resolved` to their views. The redirect-domains screen also keeps its `rows`.

**Lang.** `lang/en/cortex.php` gains `mcp` ("MCP") and `owned_redirect_domains_hint`, and reuses `domain`, `domain_hint`, `created`, `actions`, `remove_domain` and `no_redirect_domains`.

**Arch.** No new models, Data or Actions. The new classes live under `src/Atrium` (not `Domains/*/Models` or `Data`), so the model and Data arch rules don't apply. The workbench `Organization` model and its migration sit under `workbench/`, which the table-literal arch rule does not scan.

## Out of scope

- Roster's own changes: deleting its copy, its `?tab=mcp` fallback and declaring `roster.organization` and `roster.user` belong to Roster's `adopt-atrium-screen-registry` spec.
- History, activity and audit UI (Keen's spec).
- Any host-permission check from the target package, such as Roster's `roster.organizations.update` (see Q-1).
- New JSON or MCP surfaces. The owner forms reuse the existing UI routes.

## Risks and open questions

- **Q-1. Who may manage an owner's domains on a host screen?** Roster required `roster.organizations.update` (organization tab) or `roster.users.update` (user add/remove). Cortex can't name those permissions in code.
  - **(a)** Cortex's `RedirectDomainPolicy` only, with the owner. The bundled policy allows everyone, as the JSON API and MCP already do for owned domains. Apps restrict it with their own policy class. This loosens the UI compared with Roster's defaults.
  - **(b)** A per-entry config permission, e.g. `'roster.organization' => ['slot' => 'tab', 'permission' => 'roster.organizations.update']`, applied through `permission()` and the host's `access()`. This hides the UI, but Cortex's POST routes can't ask the host's access closure, so the routes stay at (a)'s level.
  - **(c)** (a), and also ship the bundled policy's `create`/`delete` for a non-null owner as `$user->can('update', $owner)`, so the owner's own policy decides.

  Recommendation: (c), because it keeps the old protection where hosts register Gate policies, names no package, and applies to the API too. It is a behaviour change for API callers, so the user should confirm it. The spec is written against (a); choosing (c) adds one criterion.
- **Q-2. Running without Atrium.** This spec runs it in process (the PSR-4 prefix removed, inside `composer test`) and also in a CI lane with Atrium uninstalled. Options: in-process only, CI only, or both. Recommendation: both, because the in-process run keeps "done" local and the CI lane catches classmap or autoload-file leaks.
- **Q-3. Removing redirect target.** `url()->previous()` limited to the same host is used instead of naming the host's route. If the referrer is missing, the user lands on Cortex's redirect-domains index, which is acceptable.
- **Risks:**
  - Grouped merge: Atrium PR #16 removes the components, and Roster deletes its copy. Merge Atrium, Keen, Roster and Cortex together, or Roster's MCP tab disappears in between.
  - The owner's morph class appears in a hidden field. It is no secret, and `RedirectDomains::owner()` rejects non-models.
  - Moving Atrium to `require-dev` breaks apps that relied on Cortex pulling it in. The CHANGELOG says so.
