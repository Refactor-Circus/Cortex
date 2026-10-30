# Register Roster redirect domains

## Goal

Cortex owns the MCP redirect-domain UI for any owner model, placed on other packages' screens only through editable defaults in `config/cortex.php`. By default these are Roster's organization tab and user aside, which replaces the copy Roster renders today. Who may add or remove an owner's domains is decided by the owner's own policy: the bundled `RedirectDomainPolicy` asks `$user->can('update', $owner)` for owned domains, on the UI, the JSON API and MCP alike (D-022, D-023, D-027). Cortex also declares every one of its own Atrium screens with the standard slots and tab bar. It stops using Atrium's removed history components, suggests Atrium instead of requiring it, refuses an Atrium too old for the screen registry, and proves it runs without Atrium both in process and in CI (D-020, D-025). The work stays one spec, merged together with Atrium, Keen and Roster (D-024). Its four deliverables: (1) redirect domains on other packages' screens, (2) owner-decided authorization for owned domains, (3) Cortex's own 11 declared screens, (4) history removal and Atrium as a suggestion.

**Depends on.** The Atrium `screen-extensions-registry` spec, on the Atrium branch or release that contains it (Atrium PR #16, which also removes the audit-trail components). The suite runs against that Atrium version as a `require-dev` dependency. Atrium behaviour this spec relies on, restated so the criteria stand alone:
- A registered tab or section renders only on a screen that is declared with that key and resolved (Atrium criterion 1).
- `?tab=<key>` for a tab that is not visible to the viewer answers 403 (Atrium criterion 7).
- The host's own `access()` runs before extension `authorize()` closures; `resolve()` runs after the host authorized the request (Atrium criterion 9).
- Slot layout: an open tab's panel replaces the page content while `header.actions` stays; `aside` renders in a right-hand column only when it has visible sections; `row.actions` renders in each row's last cell (Atrium criterion 15).
- Atrium throws `InvalidArgumentException` for a malformed target or slot.

## Acceptance criteria

### Redirect domains on other packages' screens

1. **Config.** `config/cortex.php` gains `atrium.redirect_domains.screens` under the existing `atrium` section, with a banner. It defaults to `['roster.organization' => 'tab', 'roster.user' => 'aside']`. Keys are screen keys; a value is `'tab'` or the name of a slot. Proven by a config test that reads the default. An app that published `config/cortex.php` before this change has an `atrium` array without `redirect_domains`; `ScreenRegistrations` then uses the default map (when the key is absent, not when it is an empty array, which registers nothing). A test with a published-style config lacking the key asserts the defaults still register. No Cortex PHP code names `roster` or a Roster class: a test greps every file under `src/` for `roster` (case-insensitive) and finds nothing at all, comments included.
2. **Registration.** While Atrium is installed and `cortex.ui.enabled` is true, Cortex registers, from `boot()`, one entry per config entry:
   - for `'tab'`: `Atrium::screens()->tab($target, TabDefinition::make('cortex.mcp')->label(__('cortex::cortex.mcp'))->icon('globe-alt')...)`
   - for any other value: `Atrium::screens()->section($target, $slot, SectionDefinition::make('cortex.redirect-domains')->label(__('cortex::cortex.redirect_domains'))...)`

   An empty list registers nothing. A malformed entry is a value that is not a string, an empty string, or a target or slot name Atrium rejects. Per the suite rule (D-021, same as Atrium D-022): in the `local` and `testing` environments a malformed entry throws `InvalidArgumentException` from `boot()`; in any other environment (production) it is reported once with `report()`, skipped, and the remaining entries still register, so the app keeps booting. The README says so. Tests:
   - in `testing`, `['roster.organization' => null]`, `['roster.organization' => '']` and `['roster.organization' => 'bogus slot']` each throw `InvalidArgumentException`
   - with the environment set to `production` and `Exceptions::fake()`, a map holding one malformed and one valid entry boots without throwing, reports exactly one `InvalidArgumentException`, and registers the valid entry
   - a unit-level feature test reads `ScreenExtensions::tabs('roster.organization')` and `sections('roster.user', 'aside')` for the defaults; a custom list (`['acme.account' => 'before']`) registers only that entry; `cortex.ui.enabled = false` registers nothing
3. **Visibility.** The tab and the section are visible (through `authorize()`, never `permission()` or `public()`) only when all of these hold. A feature test covers each case being false on its own, for both the tab and the section:
   - `RedirectDomains::enabled()` is true.
   - The record is a saved Eloquent model (`$record instanceof Model && $record->exists`).
   - Cortex's Atrium plugin is authorized for the request: `PluginRegistry::authorized($request)` has the key `cortex`, so its features and its routes are on.
   - The viewer passes `viewAny` on `RedirectDomainModel` with the owner, through Cortex's `ScreenAccess::allowsFor($request->user(), 'viewAny', RedirectDomainModel::class, [$record])`.

   The closure is cheap and safe: it runs no query when `enabled()` is false or the record is not a saved model, and an exception thrown by a policy is caught and treated as hidden, so a failing policy never breaks the host page. Tests: a query-count assertion (`DB::getQueryLog()`) shows no query for a non-model record and none when `enabled()` is false; the aside section on a host page showing several records adds no more than one policy query per record; a policy that throws leaves the host page answering 200 without the section.
4. **Owner.** The owner is the screen's record (the row, inside a row slot). The resolver returns `['domains' => ListRedirectDomainsAction` for that owner, `'ownerType' => $record->getMorphClass()`, `'ownerId' => (string) $record->getKey()]`. The forms post `owner_type` and `owner_id`, and the controller resolves them with the existing `RedirectDomains::owner()`. Proven by the feature tests in criterion 6, which use workbench models with no morph alias (FQCN type) and one with a morph alias.
5. **Rendering.** The tab panel and the section render one view, `cortex::ui.screens.redirect-domains`, built from `x-atrium::*` only. It renders:
   - the hint (`cortex::cortex.owned_redirect_domains_hint`)
   - an add form when the viewer passes `create` with the owner. It has `data-testid="add-redirect-domain"` and posts to `atrium.cortex.redirect-domains.store` with hidden `owner_type`/`owner_id`.
   - a table of the owner's domains (`data-testid="owned-redirect-domains"`), with a remove button (`data-testid="remove-redirect-domain"`) on each row the viewer may `delete`
   - `x-atrium::empty-state` when the owner has none

   `StylesTest` covers the new view.
6. **Moved behaviour (ported from Roster's `tests/Cortex/RedirectDomainsTest.php`).** `tests/Feature/Ui/OwnedRedirectDomainsTest.php` runs against the workbench test host (criterion 9), signed in as a workbench `User` whom the workbench owner policies (criterion 9) allow to `update` the owner, unless a case says otherwise. It covers:
   - **Organization tab:** `GET` the host `roster.organization` screen with `?tab=cortex.mcp` shows the panel. Posting `domain=claude.ai` with the owner stores `https://claude.ai` for that owner and redirects back to the host page (`->from($hostUrl)`). The domain is then listed, and removing it deletes it.
   - **Denied by policy:** with a fixture `RedirectDomainPolicy` that denies `viewAny` for an owner, the tab is absent and `?tab=cortex.mcp` answers 403 (Atrium criterion 7). With one that denies `create`, a POST with that owner answers 403 and stores nothing.
   - **Denied by the owner's policy (bundled policy, criterion 8):** with the workbench owner policy denying `update`, the panel lists the domains without the add form or remove buttons, a POST with that owner answers 403 and stores nothing, and a DELETE answers 403 and keeps the domain.
   - **Owner-scoped removal:** a `DELETE` naming owner A for a domain of owner B answers 404, and B's domain remains.
   - **Validation:** an invalid value redirects back with an error on `domain` and stores nothing.
   - **User aside:** the host `roster.user` screen renders the section in `aside` (`data-testid="section-cortex.redirect-domains"`), and add and remove work as above.
   - **Read-only:** when `viewAny` passes but `create` and `delete` are denied, the list shows without the add form or remove buttons.
   - **Switched off:** with `cortex.redirect_domains.enabled = false`, neither the tab nor the section renders.
   - **Morph alias:** register a morph alias for a workbench model. `GET` the tab and assert the hidden `owner_type` equals the alias. `POST` and `DELETE` through it, and assert the domain is stored against that owner. An unregistered type answers 404.
   - **Visibility negatives (criterion 3):** a `VisibilityTest` dataset, one case per condition false on its own, for the tab and the section: `cortex.redirect_domains.enabled = false`; Cortex's plugin not authorized (`cortex.features` or `cortex.routes.enabled` off, so `PluginRegistry::authorized()` lacks `cortex`); an unsaved record (`new Organization`) and a non-Model record; a fixture policy denying `viewAny`.
7. **Controller changes.** `RefactorCircus\Cortex\Atrium\Http\Controllers\RedirectDomainUiController::store` and `destroy` accept optional `owner_type` and `owner_id` (validated as the JSON API's `ownerRules()` validates them):
   - **Owner types (D-028):** `owner_type` must be a morph-map alias or an FQCN that `RedirectDomains::owner()` already resolves; there is no new config allow-list and nothing wider is accepted. A non-model class, an Eloquent model it does not allow, or a malformed `owner_id` answers 404 or 422 and writes nothing, on both `store` and `destroy`.
   - **Partial owner:** `owner_type` without `owner_id`, or the reverse, or either as an empty string, answers 422 with an error on the missing field and stores nothing (it is not the legacy no-owner path). Only both absent takes the no-owner path.
   - **With an owner:** `store` authorizes `create` with `[$owner]` and creates for the owner. `destroy` finds the domain with `RedirectDomainModel::query()->ownedBy($owner)->whereKey($domain)->firstOrFail()` and authorizes `delete`. An unknown owner answers 404.
   - **Redirect target (D-026):** after an owned `store` or `destroy`, the controller redirects to the path and query of `url()->previous()` only when its scheme, host and port equal `$request->getSchemeAndHttpHost()` (parsed with `parse_url`, compared exactly, no prefix match), or else to `atrium.cortex.redirect-domains.index`. A missing referrer also falls back to the index.
   - **Without an owner:** behaviour is unchanged. The existing test `manages domains from the dashboard` passes unmodified.
   - **Tests:** a feature test covers the same-host redirect (fixture: the request's own host, `http://localhost` in the test case), the foreign-host fallback, the unknown owner, the partial-owner cases, the owner-type rejections above (a non-model class, a non-Eloquent class, an unknown alias), and three fallbacks to the index route: a lookalike host (`http://localhost.evil.test`), a userinfo form (`http://localhost@evil.test`) and a scheme mismatch (`https://localhost` against an `http` request).

   No new routes are added.

### Owner-decided authorization for owned domains

8. **Bundled policy asks the owner (D-022, D-023, D-027).** `RefactorCircus\Cortex\Domains\RedirectDomain\Policies\RedirectDomainPolicy` changes for owned domains only:
   - `create(?Authenticatable $user, ?Model $owner = null)`: `true` when `$owner` is null (unchanged); otherwise `$user !== null && $user->can('update', $owner)`.
   - `delete(?Authenticatable $user, RedirectDomainModel $domain)`: `true` when `$domain->owner_type` is null (unchanged); otherwise the owner is loaded through the existing `owner` relation and the answer is `$user !== null && $owner !== null && $user->can('update', $owner)`. A domain whose owner row no longer exists is denied (fails closed).
   - `viewAny` is unchanged (allows everyone, guests included).

   Because the UI controller, the JSON API and the MCP tools all authorize through this policy, the change applies to all three. An app that sets its own class in `cortex.policies` is unaffected. The class docblock is updated to say so. Tests in `tests/Feature/OwnedRedirectDomainPolicyTest.php`, each over the JSON API, MCP and the Gate directly:
   - a guest is denied `create` and `delete` for an owned domain (JSON `POST`/`DELETE` answer 403, MCP create and delete fail, nothing is written or removed)
   - a signed-in user without `update` on the owner (workbench owner policy denies) is denied the same way
   - a signed-in user with `update` on the owner is allowed
   - the null-owner path is unchanged: a guest and a signed-in user may still `create` and `delete` a domain without an owner
   - a domain whose owner row was deleted cannot be deleted through the policy

   `CHANGELOG.md` gains a line for this behaviour change (criterion 18).

9. **Test host.** The tests use a workbench test-host screen, not Roster as a dev dependency:
   - `Workbench\App\Http\Controllers\TestHostScreenController` serves `GET test-host/{key}/{id?}` with `web` middleware, registered by the test case's `defineRoutes()`.
   - Its key is a route parameter, so one controller serves `roster.organization`, `roster.user` and `acme.account`.
   - It declares `Screen::standard($key, Screen::DETAIL)->record($model)` with a Gate-based `access()`, resolves it, and renders a workbench view with `x-atrium::screen-tabs`, `screen-tab-panel` and `screen-sections` for each standard slot, laid out per Atrium criterion 15.
   - The record is a `Workbench\App\Models\Organization` (workbench migration, loaded from `workbench/database/migrations` by the test case) for `roster.organization`, and the workbench `User` for `roster.user`.
   - Owner policies for criterion 8: `Workbench\App\Policies\OrganizationPolicy` and `Workbench\App\Policies\UserPolicy`, registered with `Gate::policy()` in the test case, allow `update` to any signed-in user by default. A test toggles them to deny with a static flag reset in `tearDown`.

   `composer.json` gains no Roster dependency (asserted by criterion 16's test).

### Cortex's own screens

10. **Declared screens.** Every Cortex page with a controller view declares itself with `Screen::standard()` and resolves it after its own authorization. Each has only the synthetic `overview` tab built in, which is the default tab:

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

    A feature test with a dataset over all 11 screens registers a tab and a section in every standard slot (`header.actions`, `before`, `after`, plus `aside` on detail or `row.actions` on list screens, with at least one row present). It asserts each section renders in its slot, and that the tab appears and opens through `?tab=`. Leakage is checked per kind through dataset columns: on every detail screen a section registered in `row.actions` must not render; on every list screen a section registered in `aside` must not render. No screen has a `history` slot. For `cortex.redirect-domains`, a configured (non-stored) row gets no `row.actions` section, asserted explicitly.
11. **Access closure.** Each screen's `access()` is `fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allowsFor($request->user(), $permission, $record ?? $subject)`, with `$subject` from the table. On `cortex.run`, a null record denies. Tests:
    - a `permission('view')` section on `cortex.virtual-agent` shows when the fixture policy allows and hides when it denies
    - a `permission('viewAny')` section on `cortex.virtual-agents` follows the class check
    - a `permission()` section on `cortex.run` never shows
12. **Layout.** Per Atrium criterion 15, an open registered tab's panel replaces the page content, and only `header.actions` stays. `header.actions` sections render inside `x-atrium::page-header`'s actions, after the page's own actions. `before` and `after` render around the content. `aside` renders in a right-hand column only when it has visible sections. `row.actions` renders inside each row's last cell, after its own controls. These are asserted on rendered HTML, not by eye: Atrium's aside landmark (`data-testid="screen-aside"`) is present only when a visible aside section exists and absent otherwise; `assertSeeInOrder` shows a `header.actions` section after the page's own actions, and a `row.actions` section after the row's own controls and inside the row's last `td`; with a tab open, `assertDontSee` on page-only content passes while the `header.actions` section is still seen. With nothing registered, the markup is unchanged: every existing feature test passes without modification, except those listed in criterion 14.

### History removal and Atrium as a suggestion

13. **No history in Cortex's UI.** The `<x-atrium::audit-trail>` calls in `ui/virtual-agents/index`, `ui/virtual-agents/form` and `ui/partials/override` are removed, and `CortexPlugin::navigation()` no longer calls `historyNavItem()`. Screens declare no audit subject (Keen attaches History by wildcard). The Keystone JSON route `GET cortex/history` and the history MCP tool are unaffected (`tests/Feature/HistoryTest.php` passes unchanged). A test asserts that the plugin's navigation has no item whose route is `atrium.history.show`. A grep of `resources`, `src`, `README.md`, `CLAUDE.md`, `docs/agent/standards` and `resources/boost` for `audit-trail` and `historyNavItem` finds nothing.
14. **Forced test changes.** Existing tests may change only where this spec's decisions force it, and the PR lists each by file and test name. The expected set is:
    - `tests/Feature/Ui/AuditTrailTest.php`: deleted (the component is gone from Atrium).
    - `tests/Feature/HistoryNavigationTest.php`, `links its own audit log from its sidebar group`: replaced by criterion 13's assertion.
    - `tests/Pest.php` and `tests/TestCase.php`: gain `ResetsScreenExtensions`, the test-host routes and migrations, and the workbench owner policies.
    - `tests/Feature/RedirectDomainsTest.php`, `manages domains over the json api` and `creates and deletes domains over mcp with parity to the http payload`: sign in a user before the owned calls, because criterion 8 denies guests for owned domains. Their assertions do not change.
15. **Composer.** `refactor-circus/atrium` moves from `require` to `require-dev` (the suite needs it), and it is added to `suggest` with a description. Keystone stays the only suite package in `require`. `composer.json` gains a `conflict` entry for `refactor-circus/atrium` on versions older than the first release that contains the screen-extensions registry (Atrium PR #16), so a mismatched install fails at `composer update` (D-020). The constraint is `<V`, where V is that Atrium release; Atrium has no tag yet, so the builder takes V from Atrium's release at grouped-merge time and, if none is tagged when the group is built, uses `<1.0.0` and records that in the PR. A test reads `composer.json` and asserts:
    - `require` has no `refactor-circus/*` key other than `refactor-circus/keystone`
    - `suggest` has `refactor-circus/atrium`
    - `require-dev` has `refactor-circus/atrium`
    - `conflict` has a non-empty `refactor-circus/atrium` constraint starting with `<`
16. **Arch test.** `tests/ArchTest.php` gains `arch('only the Atrium layer uses Atrium')->expect('RefactorCircus\Cortex')->not->toUse('RefactorCircus\Atrium')->ignoring('RefactorCircus\Cortex\Atrium')`, and it passes. Registration lives in `RefactorCircus\Cortex\Atrium\ScreenRegistrations` (below). `CortexServiceProvider` reaches it only behind the same string `class_exists('RefactorCircus\Atrium\Atrium')` and `cortex.ui.enabled` guard that `registerAtriumPlugin()` uses. A test asserts `composer.json` lists no `refactor-circus/roster`.
17. **No-Atrium proof, both ways (D-020, D-025, D-029, D-030).** A `WithoutAtrium` suite proves Cortex boots and serves its HTTP API and MCP without Atrium, and a CI lane proves it with Atrium uninstalled:
    - **Files:** `phpunit.without-atrium.xml`, bootstrap `tests/WithoutAtrium/bootstrap.php`, tests in `tests/WithoutAtrium`, excluded from the default `phpunit.xml.dist` suite. The bootstrap loads Composer's autoloader and removes the `RefactorCircus\Atrium\` PSR-4 prefix, so no Atrium class can load. It fails loudly (throws) if `ClassLoader::findFile('RefactorCircus\Atrium\Atrium')` still resolves, and the suite must not run with an optimized or authoritative classmap. Its `WithoutAtriumTestCase` registers no Atrium provider. The in-process run cannot see classmap or Composer `files` leaks, so the CI lane is the authoritative proof and is verified only by the workflow run, not by `composer test`. The README says so.
    - **Assertions:**
      - the PSR-4 prefixes of Composer's `ClassLoader` no longer contain `RefactorCircus\Atrium\` (first test, so the removal is proven before the rest)
      - `class_exists('RefactorCircus\Atrium\Atrium')` is false
      - the app boots with `CortexServiceProvider`, with the default `redirect_domains.screens` map present (no registration, no error)
      - no route name starts with `atrium.`
      - `cortex.redirect-domains.index` (JSON) answers 200 and `store` creates a domain
      - `mcpTool(ListRedirectDomainsTool::class)` answers
      - a virtual agent can be created over the JSON API
    - **Gate:** a composer script `test:without-atrium` runs it, and `composer test` calls it after `@test:unit`.
    - **CI:** `.github/workflows/tests.yml` gains a `without-atrium` job that runs `composer remove --dev refactor-circus/atrium --no-update`, installs, and runs `composer test:without-atrium`.

### Documentation

18. **Docs.** Three places change:
    - **README:** a "Redirect domains on other screens" section documents `cortex.atrium.redirect_domains.screens`, the `cortex.mcp` tab key, the `cortex.redirect-domains` section key, the visibility rules from criterion 3, and that for an owned domain the bundled policy asks `update` on the owner (criterion 8), which apps can replace through `cortex.policies`. It also states "Atrium is suggested, not required" and that without Atrium the dashboard and these screens are simply absent (no error, no log; the HTTP API and MCP keep working). It documents that a malformed `redirect_domains.screens` entry throws at boot in local and testing and is reported and skipped in production, that older Atrium versions are refused through `conflict`, and that the CI lane is the authoritative no-Atrium proof.
    - **Local standard:** `docs/agent/standards/cortex/atrium-screens.md` replaces its "History" section with the screen-declaration rule: keys, standard slots, the access closure, and no audit components.
    - **Other docs and changelog:** `CLAUDE.md` and the Boost skill drop their audit-trail and history-nav text. `CHANGELOG.md` has an upgrade note covering:
      - Atrium moved to `suggest` (apps using the dashboard require it themselves), and a `conflict` refuses Atrium versions older than the screen registry
      - the new config key (apps with a published config get the default map when the key is absent)
      - the `owner_type`/`owner_id` fields on the UI store and destroy routes
      - owned domains: the bundled policy now asks `update` on the owner for `create` and `delete`, over the UI, the JSON API and MCP, so guests and users without it are refused; domains without an owner are unchanged
      - removed history UI (Keen provides it)
      - the Roster tab key changes from `?tab=mcp` to `cortex.mcp` and the old Roster-named route names are gone (the moved UI routes are `atrium.cortex.redirect-domains.*`)
      - grouped merge with Atrium, Keen and Roster

    A docs test asserts the README has the heading "Redirect domains on other screens" and contains `redirect_domains.screens`, `cortex.mcp` and the exact phrase `Atrium is suggested`. A CHANGELOG test asserts the upgrade note contains a keyword for each of the seven topics above (`suggest`, `redirect_domains`, `owner_type`, `update`, `History`, `?tab=mcp`, `merge`) and the word `conflict`.
19. Done means `composer test` passes, including `test:without-atrium`, and `vendor/bin/pest` passes. Cortex has no browser suite, so there is no `test:browser` step.

## Design

**Placement.** `RefactorCircus\Cortex\Atrium\ScreenRegistrations` (final, static `register(): void`) reads `cortex.atrium.redirect_domains.screens` and calls `Atrium::screens()->tab()` or `section()` per entry. Each entry is validated and registered inside its own `try`: a non-string or empty value throws `InvalidArgumentException`, and Atrium's own validation throws it for a malformed target or slot. The `catch` rethrows when `app()->environment('local', 'testing')`; otherwise it calls `report($e)` and moves on to the next entry (D-021). Throwing a plain `InvalidArgumentException` breaks Cortex's convention that every thrown exception extends `CortexException`; the suite decision (D-021, matching Atrium D-022) wins, so the builder adds a row to `docs/agent/standards-feedback.md` saying so. It falls back to the default map only when the key is absent (criterion 1). `CortexServiceProvider::boot()` calls it from a private `registerAtriumScreens()`, right after `registerAtriumPlugin()` and with the same guard. A target screen that nobody declares never renders (Atrium criterion 1).

**Definitions.** Tab `cortex.mcp` has the label `cortex::cortex.mcp` ("MCP") and icon `globe-alt`. Section `cortex.redirect-domains` has the label `cortex::cortex.redirect_domains`. Both use `view('cortex::ui.screens.redirect-domains')`, and they share one `authorize` closure (criterion 3) and one resolver (criterion 4). These closures run on every target screen's render, so they return early: they check `enabled()` and `$record?->exists` first and query nothing.

**Owner.** The screen's record is the owner. Its type in the form is `getMorphClass()`, so the morph map, already the registry `RedirectDomains::owner()` reads, decides the type string (D-028). Cortex never inspects which package the model belongs to (D-001, D-002 settled by the addendum).

**Authorization.** All of it is Cortex's own: `RedirectDomainPolicy` `viewAny`/`create` with the owner, and `delete` on the domain, through `cortex.policies`. These are the same checks the JSON API and MCP tools make. For an owned domain the bundled `create` and `delete` delegate to the owner's own policy with `$user->can('update', $owner)` (criterion 8), so a host that registers a policy for its model (Roster's organization and user policies) keeps the protection Roster's own UI had, without Cortex naming any host permission. The visibility check stays at `viewAny` (unchanged, allows everyone), and the add form and remove buttons follow `create` and `delete`. No per-entry config permission is added (D-023 chose (c) on top of (a)). The host's own authorization has already run before `resolve()` (Atrium criterion 9).

**Redirect after owned writes.** Same-origin `url()->previous()`, else `atrium.cortex.redirect-domains.index` (D-026, criterion 7).

**Screen helper.** `RefactorCircus\Cortex\Atrium\Screens::declare(string $key, string $kind, ?Model $record, ?string $subject): Screen` (final, static) builds `Screen::standard()`, sets the record, and adds the access closure from criterion 11, so the 11 controllers share one definition. Controllers inject `ScreenExtensions` (Atrium's) and pass `resolved` to their views. The redirect-domains screen also keeps its `rows`.

**Lang.** `lang/en/cortex.php` gains `mcp` ("MCP") and `owned_redirect_domains_hint`, and reuses `domain`, `domain_hint`, `created`, `actions`, `remove_domain` and `no_redirect_domains`.

**Composer.** Atrium in `require-dev` and `suggest`, plus a `conflict` on Atrium older than the screen registry (criterion 15, D-020). Keystone is the only suite package in `require`.

**Arch.** No new models, Data or Actions. The new classes live under `src/Atrium` (not `Domains/*/Models` or `Data`), so the model and Data arch rules don't apply. The policy change edits an existing class. The workbench `Organization` model, its migration and the workbench owner policies sit under `workbench/`, which the table-literal arch rule does not scan.

## Out of scope

- Roster's own changes: deleting its copy, its `?tab=mcp` fallback and declaring `roster.organization` and `roster.user` belong to Roster's `adopt-atrium-screen-registry` spec.
- History, activity and audit UI (Keen's spec). Cortex declares no `history` slot and no audit subject; Keen attaches History by wildcard.
- Old `?tab=mcp` links: no removal or migration of them (they fall back to the default tab, D-003).
- Configured (non-stored) redirect-domain rows get no row slot.
- Cortex's dashboard behaviour, the JSON API and MCP for domains without an owner are unchanged.
- Any host-permission check named in Cortex config or code, such as Roster's `roster.organizations.update`. The owner's own policy decides instead (criterion 8).
- A config allow-list of owner types (D-028).
- New JSON or MCP surfaces. The owner forms reuse the existing UI routes.
- Splitting this spec (D-024).

## Risks

- **Grouped merge.** Atrium PR #16 removes the components, and Roster deletes its copy. Merge Atrium, Keen, Roster and Cortex together, or Roster's MCP tab disappears in between.
- **Behaviour change for API and MCP callers (criterion 8).** Guests and users without `update` on an owner can no longer create or delete that owner's domains. An owner model with no policy and no Gate ability answers `update` as denied, so apps that used owned domains without an owner policy must add one or set their own class in `cortex.policies`. The CHANGELOG says so.
- **Atrium version for `conflict`.** Atrium has no release tag yet; the constraint needs the version that ships PR #16 (criterion 15). If it is not tagged when the group is built, `<1.0.0` is used and the PR records it, so Atrium's registry release must be 1.0.0 or later.
- **Production config errors.** A malformed `redirect_domains.screens` entry is skipped silently apart from one report in production, so the UI for that entry is missing until the report is acted on.
- The owner's morph class appears in a hidden field. It is no secret, and `RedirectDomains::owner()` rejects non-models.
- Moving Atrium to `require-dev` breaks apps that relied on Cortex pulling it in. The CHANGELOG says so.

## Changelog

- 2026-10-29, approved: folded D-020 (both-ways no-Atrium proof, Atrium `conflict`, criteria 15 and 17), D-021 (throw in local/testing, report and skip in production, criterion 2), D-022, D-023 and D-027 (bundled policy asks `update` on the owner for owned `create`/`delete`, new criterion 8, forced test changes in criterion 14, CHANGELOG line), D-024 (one spec), D-025, D-029 and D-030 (both proofs), D-026 (same-origin redirect, criterion 7), D-028 (no owner-type allow-list, rejection tests in criterion 7). Criteria renumbered from 8 on. Open questions Q-1 to Q-3 removed as decided.
