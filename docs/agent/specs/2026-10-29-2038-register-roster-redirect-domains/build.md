# Build: Register Roster redirect domains

## Group 1

Owner-decided policy and owner-aware UI controller (criteria 7, 8, 14). Branch `ring-master/2026-10-29-2038-register-roster-redirect-domains--g1`, commits `755914b` and `6b56c5d` (retry).

### Files

- `src/Domains/RedirectDomain/Policies/RedirectDomainPolicy.php`: for owned domains, `create` and `delete` need a signed-in user who may `update` the owner. A guest is refused, and so is a domain whose owner row is gone. Null-owner domains and `viewAny` are unchanged. The docblock says the rule covers the UI, JSON API and MCP, and that a class set in `cortex.policies` replaces it.
- `src/Atrium/Http/Controllers/RedirectDomainUiController.php`: `store` and `destroy` take optional `owner_type`/`owner_id` and resolve them with `RedirectDomains::owner()`. Both fields or neither is accepted; one alone is a validation error on the missing field. `store` authorizes `create` with `[$owner]`. `destroy` finds the domain with `ownedBy($owner)->whereKey()->firstOrFail()` and authorizes `delete`. After an owned write the controller redirects to the path and query of `url()->previous()`, but only when its scheme, host and port match the request's exactly; otherwise, and when there is no referrer, it goes to `atrium.cortex.redirect-domains.index`. Without an owner nothing changes.
- `tests/Fixtures/OwnerPolicyFixture.php`: an owner policy with a static `update` switch (it allows guests too) and a call counter. Tests register it with `Gate::policy()` for `VirtualAgentModel` and reset it after each test.
- `tests/Feature/OwnedRedirectDomainPolicyTest.php`: covers a guest, a user without `update`, a user with it, the null-owner path (guest and user dataset, owner policy never asked) and a deleted owner. Each case runs over the Gate, the JSON API and MCP.
- `tests/Feature/Ui/RedirectDomainOwnerControllerTest.php`: covers:
  - owner by FQCN and by morph alias
  - owner-type rejections: `stdClass`, `Collection`, the abstract `Model`, a missing class, an unknown alias
  - malformed `owner_id`: array, too long, unknown key
  - partial owner, four cases
  - unknown owner
  - owner-scoped destroy (another owner's domain and a global domain both answer 404)
  - authorization through the owner policy
  - the redirect dataset: same host with and without a query, a protocol-relative path kept on the own host, foreign host, lookalike `localhost.evil.test`, userinfo `localhost@evil.test`, scheme mismatch, another port
  - a missing referrer
  - writes without an owner still going to the index

  On the retry, its global helpers were renamed to `ownerControllerFields()`, `ownerControllerDomain()` and `OWNER_CONTROLLER_HOST_PAGE`. Pest loads every test file into one process, so the generic names could have clashed with group 3's `OwnedRedirectDomainsTest`.
- `tests/Feature/RedirectDomainsTest.php` (criterion 14): `manages domains over the json api` and `creates and deletes domains over mcp with parity to the http payload` now sign in a `GenericUser` before their owned calls. Their assertions are unchanged.

### Tests

Following the prompt, the suite was not run here; the gate runs it. Pint passes on every changed file, and PHPStan passes on both changed `src` files.

### Deviations

- **Guest refusal status over JSON (criterion 8).** The spec says a guest's owned JSON `POST`/`DELETE` answer 403. The `http/authorization` standard and Cortex's `CLAUDE.md` say a refused guest gets 401 and a refused signed-in user 403, and Keystone's request base does exactly that. So the test asserts 401 for the guest and 403 for the signed-in user without `update`. MCP refusals are `Unauthorized.` in both cases.
- **Partial owner "answers 422" (criterion 7).** This is a validation error. JSON requests get 422 with the error on the missing field, which the tests assert. A form post gets Laravel's usual redirect back to the page with the error in the session, the same as criterion 6's `domain` validation; the tests assert that too.
- **Owner check through `Gate::forUser($user)->allows('update', $owner)`** instead of `$user->can('update', $owner)`. It gives the same answer for Laravel users and also works for an `Authenticatable` without `Authorizable`, such as `GenericUser`, which the tests use. It is also the form the `http/authorization` standard shows for deferring to another model's policy.
- **Owner validation rules in the controller.** The controller validates the owner with `ownerRules()` plus `required_with:owner_id` on `owner_type`. The JSON request's `ownerRules()` is protected, and on its own it does not reject an `owner_id` sent without `owner_type`, which criterion 7 requires. The JSON API's own rules are unchanged.
- **Test owner model.** The tests use `VirtualAgentModel` as the owner with `OwnerPolicyFixture` as its policy. They do not use the workbench `Organization`/`User` from group 2, which is being built at the same time.

## Group 2

Composer, arch rule, test host and the no-Atrium proof (criteria 9, 14, 15, 16, 17). Branch `ring-master/2026-10-29-2038-register-roster-redirect-domains--g2`, commit `2c25a5a`.

### Files

- `composer.json`: `refactor-circus/atrium` moves from `require` to `require-dev` and is added to `suggest` with a description. A new `conflict` sets `"refactor-circus/atrium": "<0.1.0"`. A new `test:without-atrium` script runs `vendor/bin/pest --configuration=phpunit.without-atrium.xml`, and `composer test` calls it right after `@test:unit`. Keystone is the only suite package left in `require`.
- `composer.local.json` (gitignored, not committed): the same changes were copied in by hand. Its Atrium path repository now points at `../Atrium--2026-10-29-2037-screen-extensions-registry` with the version `dev-main`. It was not regenerated.
- `tests/ArchTest.php`: adds `arch('only the Atrium layer uses Atrium')`, worded exactly as criterion 16. It covers only `src/`, because Pest's arch layer resolves `RefactorCircus\Cortex` to that PSR-4 prefix and not to `RefactorCircus\Cortex\Tests`.
- `tests/TestCase.php` (criterion 14):
  - uses Atrium's `ResetsScreenExtensions`
  - registers `OrganizationPolicy` and `UserPolicy` with `Gate::policy()` in `setUp()`, and resets their `$deniesUpdate` flags in `tearDown()`
  - `defineRoutes()` adds the `workbench` view namespace and the route `GET test-host/{key}/{id?}`, named `test-host`, with `web` middleware
  - `defineDatabaseMigrations()` also loads Testbench's Laravel migrations (the users table for the workbench `User`) and `workbench/database/migrations`
- `tests/Pest.php` (criterion 14): binds `WithoutAtriumTestCase` to `tests/WithoutAtrium`, and comments on what `TestCase` now carries.
- `workbench/app/Http/Controllers/TestHostScreenController.php`: a single invokable controller. It first authorizes `view` on the record through the Gate, then declares `Screen::standard($key, Screen::DETAIL)->record($record)` with a Gate-based `access()`, resolves it with `ScreenExtensions`, and renders `workbench::test-host`. For `roster.user` the record is the workbench `User`; for any other key it is an `Organization`. Without an id, the record is an unsaved model of that class, for criterion 6's unsaved-record case. An unknown id answers 404.
- `workbench/app/Models/Organization.php` and `workbench/database/migrations/2026_10_29_000001_create_organizations_table.php`: the owner model, with no morph alias, so its type is its class name.
- `workbench/app/Policies/OrganizationPolicy.php` and `UserPolicy.php`: `view` allows anyone. `update` allows any signed-in user unless the static `$deniesUpdate` is set.
- `workbench/resources/views/test-host.blade.php`: laid out per Atrium criterion 15, the same way as Atrium's `settings/show`:
  - `header.actions` renders in the page header's actions, after a host badge (`data-testid="host-action"`)
  - next comes `screen-tabs`
  - an open registered tab's `screen-tab-panel` replaces the content
  - otherwise `before` renders, then the content (`data-testid="test-host-content"`), then `after`
  - `aside` gets a right-hand column (`data-testid="screen-aside"`) only when it has visible sections
- `phpunit.without-atrium.xml`: runs only `tests/WithoutAtrium`, in file order (`executionOrder="default"`), with `tests/WithoutAtrium/bootstrap.php`.
- `tests/WithoutAtrium/bootstrap.php`: loads Composer's autoloader and removes the `RefactorCircus\Atrium\` PSR-4 prefix from every registered `ClassLoader`. It throws if:
  - an Atrium class or interface is already loaded
  - a loader is classmap-authoritative or uses APCu
  - any Atrium class is in a classmap
  - the prefix is still listed, or `findFile('RefactorCircus\Atrium\Atrium')` still resolves
- `tests/WithoutAtrium/WithoutAtriumTestCase.php`: Testbench with the Ai, Mcp and Cortex providers only, and the same pinned environment as `TestCase`.
- `tests/WithoutAtrium/WithoutAtriumTest.php`: criterion 17's assertions, with the PSR-4 prefix check first. The others: `class_exists('RefactorCircus\Atrium\Atrium')` is false; Cortex boots with the default `redirect_domains.screens` map; no `atrium.` route exists; the JSON index answers 200 and store creates a domain; `ListRedirectDomainsTool` answers; a virtual agent is created over the JSON API.
- `phpunit.xml.dist`: a comment saying `tests/WithoutAtrium` runs only from `phpunit.without-atrium.xml`. No default suite includes that folder.
- `.github/workflows/tests.yml`: a new `without-atrium` job (ubuntu, PHP 8.5). It runs `composer remove --dev refactor-circus/atrium --no-update`, installs, checks that `vendor/refactor-circus/atrium` is absent, and runs `composer test:without-atrium`.
- `tests/Feature/ComposerTest.php` covers:
  - `require` has no suite package but Keystone
  - Atrium is in `suggest` (with a description) and in `require-dev`, not in `require`
  - the conflict starts with `<` and is `<0.1.0`
  - there is no `refactor-circus/roster`
  - `composer test` runs `@test:without-atrium` after `@test:unit`
  - the CI workflow has the `without-atrium` job
- `tests/Feature/Ui/TestHostScreenTest.php` (outside the group's file list, required by task 2.1), a smoke test:
  - the host renders for `roster.organization`, `roster.user` and `acme.account` without the aside landmark
  - a tab registered for the key appears there and not for another key
  - an unsaved record renders, and an unknown id answers 404
  - the owner policies allow a signed-in user, deny a guest, and deny when the flag is set
  - the flags are reset for the next test

### Tests

Following the prompt, the suite was not run here; the gate runs it. Pint passes on every changed PHP file, and `composer validate` passes.

On the retry, all of `2c25a5a` was checked against the retry prompt: the conflict is exactly `<0.1.0`, `composer.local.json` mirrors it by hand, and the Atrium API it uses exists on the registry branch (`Screen::standard`, `ResolvedScreen::$openRegisteredTab`, `sections()`, `ResetsScreenExtensions`'s `setUp`/`tearDown` hooks). Nothing outside `src/Atrium` names Atrium, so the new arch rule holds. Pint and `composer validate` pass again. Nothing needed changing, so the retry adds no commit.

### Deviations

- **Conflict constraint is `<0.1.0`, not `<1.0.0`.** The spec says to use `<1.0.0` only when no Atrium release with the registry is known. The group prompt says Atrium's first release, v0.1.0, carries the registry, and Atrium's CHANGELOG has a `v0.1.0` section. `dev-main` installs are unaffected, because Composer never matches a dev branch against `<0.1.0`. Record this in the PR.
- **`cortex.atrium.redirect_domains.screens` comes from group 3.** The WithoutAtrium assertion that Cortex boots with the default map fails until group 3 adds the config key. The default `vendor/bin/pest` run does not include that suite; `composer test` does.
- **ResetsScreenExtensions is on `TestCase` rather than in `Pest.php`'s `uses()`.** Pest checks `class_exists`/`trait_exists` for every `uses()` entry when it loads `Pest.php`, which happens before the WithoutAtrium bootstrap. With the trait on the class, every subclass gets it (PennantPlus and RenamedTables modes too). Loading `Pest.php` still declares the trait itself, but no Atrium class or interface; the bootstrap refuses to run if one has been loaded.
- **Users table from `loadLaravelMigrations()`.** The workbench `User` needs a users table. A workbench migration for it would clash with the users table Testbench's skeleton already creates for `composer serve`.

## Group 3

Redirect domains on other packages' screens (criteria 1 to 6). Branch `ring-master/2026-10-29-2038-register-roster-redirect-domains--g3`, commit `2f49607`.

### Files

- `config/cortex.php`: the `atrium` section gains `redirect_domains.screens`, defaulting to `['roster.organization' => 'tab', 'roster.user' => 'aside']`. Its banner covers the keys and values, the `cortex.mcp` tab and `cortex.redirect-domains` section keys, the visibility rules, the owner-`update` rule for the add form and remove buttons, the `[]` and absent-key behaviour, and the throw-in-local/testing, report-and-skip-elsewhere rule.
- `src/Atrium/ScreenRegistrations.php` (final, static `register()`):
  - It reads the map. Only when the key is absent does it fall back to the map in the package's own `config/cortex.php`, which it `require`s, so no PHP under `src/` names a screen key. An empty list registers nothing.
  - Each entry is registered inside its own `try`. A non-string or empty target or value throws `InvalidArgumentException`, and so does a map that is not an array; Atrium throws it for a bad target or slot. The `catch` rethrows in `local` and `testing`, and otherwise calls `report()` and moves on.
  - `tab` registers `TabDefinition::make('cortex.mcp')` with the label `cortex::cortex.mcp` and the icon `globe-alt`. Any other value registers `SectionDefinition::make('cortex.redirect-domains')` with the label `cortex::cortex.redirect_domains` in that slot. Both use the view `cortex::ui.screens.redirect-domains`.
  - Both share one private `authorize` closure. It checks, in order: `RedirectDomains::enabled()`; a saved record; `cortex` in `Atrium::pluginRegistry()->authorized($request)`; then `ScreenAccess::allowsFor($user, 'viewAny', RedirectDomainModel::class, [$record])`. The first two run no query. A policy that throws is caught, reported, and counts as hidden.
  - Both share one private resolver. It returns `domains` (`ListRedirectDomainsAction` for the owner), `ownerType` (`getMorphClass()`) and `ownerId` (the string key). It sets each domain's `owner` relation to the record, so the per-row `delete` check loads no owner.
- `src/CortexServiceProvider.php`: a private `registerAtriumScreens()`, called right after `registerAtriumPlugin()`. It has the same guard: `class_exists('RefactorCircus\\Atrium\\Atrium')` as a string, and `cortex.ui.enabled === true`. `loadTranslationsFrom()` now runs before both, because the tab and section labels are translated at boot.
- `resources/views/ui/screens/redirect-domains.blade.php`: built from `x-atrium::*` only, inside a card:
  - the hint `owned_redirect_domains_hint`
  - when the viewer passes `create` with the owner, an add form (`data-testid="add-redirect-domain"`) posting to `atrium.cortex.redirect-domains.store` with hidden `owner_type` and `owner_id`
  - the owner's domains in `owned-redirect-domains`, with a remove form on each row the viewer may `delete` (`data-testid="remove-redirect-domain"`, DELETE with the same hidden fields)
  - `x-atrium::empty-state` when there are none
- `lang/en/cortex.php`: adds `mcp`, `owned_redirect_domains_hint` and `add_owned_domain` (see Deviations).
- `docs/agent/standards-feedback.md`: a row for throwing a plain `InvalidArgumentException` instead of a `CortexException` (D-021).
- `tests/Feature/Ui/ScreenRegistrationsTest.php` (criteria 1 and 2). Each case empties the registry and runs the provider's private `registerAtriumScreens()` again, so the guard is covered too. It covers:
  - the config default
  - the boot-time default registrations (keys, labels, icon, view, neither `permission()` nor `public()`)
  - a published-style `atrium` array without the key, which gets the defaults
  - an empty list, and the custom `['acme.account' => 'before']`
  - `ui.enabled` false and `'1'`, which register nothing
  - in testing, a dataset that throws: null, `''`, `bogus slot`, a non-string value, a bogus target, a list key, and a non-array map
  - in local, a throw
  - in production with `Exceptions::fake()`, exactly one reported `InvalidArgumentException`, with the valid `roster.user` aside still registered
- `tests/Feature/Ui/VisibilityTest.php` (criterion 3). Cases:
  - a dataset of tab × section × condition, each condition false on its own: stored domains off; Cortex's plugin not authorized (a required feature resolved off); an unsaved record; no record; a fixture policy denying `viewAny`. Each case checks the definition's `allows()` with a new request, and the rendered host page wherever a host can show that record.
  - a baseline where every condition holds
  - no `permission()` or `public()`
  - query log: no query for a missing or unsaved record, and none while `enabled()` is false, even with a querying policy
  - the aside resolved for three records: zero queries with the bundled policy, and at most one per record with a policy that queries
  - a throwing policy: the host answers 200 without the section, the aside landmark or the tab
- `tests/Feature/Ui/OwnedRedirectDomainsTest.php` (criteria 4, 5 and 6), against the workbench test host, signed in as a workbench `User`. It covers:
  - the organization tab: shown, opened with `?tab=cortex.mcp`, hint and form fields, empty state, add from the host URL with a redirect back, list, remove
  - only the owner's own domains listed
  - `viewAny` denied: no tab, and `?tab=` answers 403
  - `create` denied: POST answers 403
  - the owner's policy denying `update`: read-only list, POST 403, DELETE 403, for the organization and for the user
  - another owner's domain: 404
  - an invalid domain: redirected back with an error on `domain`
  - the user aside: add and remove
  - read-only when `create` and `delete` are denied
  - switched off
  - a morph alias: the hidden `owner_type` is the alias, POST and DELETE work through it, and an unregistered type answers 404
  - the owner loaded only once on a tab listing three domains
- `tests/Feature/NoRosterReferenceTest.php`: a case-insensitive grep of every file under `src/` for `roster`, comments included.
- `tests/Feature/Ui/StylesTest.php`: a new case asserts the view exists, passes `AtriumStyles` on `resources/views/ui/screens`, and uses no component namespace other than `atrium`.
- `tests/Fixtures/Policies/SwitchableRedirectDomainPolicy.php` (outside the group's file list): the bundled policy with static switches to deny abilities, throw from `viewAny`, or run one query in it. The ported cases call for a "fixture `RedirectDomainPolicy`", and both new test files share it.
- `tests/Feature/Ui/TestHostScreenTest.php` (group 2's file, outside this group's list): its smoke test `renders the workbench screen for its key and record` asserts that `roster.user` has no aside landmark. The default map now puts Cortex's section there, so the test empties the registry first, to test the host alone. Its assertions are unchanged.

### Tests

Following the prompt, the suite was not run here; the gate runs it. Pint passes on every changed PHP file, PHPStan passes on both changed `src` files, and `php -l` passes on the new test files.

### Deviations

- **One extra lang key, `add_owned_domain` ("Add domain").** The spec reuses `add_domain`, but that reads "Add global domain", which is wrong on an owner's screen.
- **Default map fallback.** The fallback reads the shipped `config/cortex.php` rather than holding a constant, because criterion 1 forbids `roster` anywhere under `src/`.
- **"Non-Model record".** Atrium types an extension's record as `?Model`, so the only non-model record that can reach the closure is null. VisibilityTest checks it as `no record` at the definition level. No host page renders without a record.
- **Plugin not authorized.** This is tested by switching off a feature in `cortex.atrium.features`. `CortexPlugin::authorize()` does not read `cortex.routes.enabled`, and `CortexPlugin` belongs to group 4, so that variant is not tested.
- **Scoped state between requests.** Atrium's `ScreenVisibility` is `scoped()`, and Laravel forgets scoped instances only between queue jobs and Octane requests, not between test requests. Tests that view a page again after a write call `app()->forgetScopedInstances()` first.
- **Policy exceptions are reported.** The authorize closure reports a throwing policy before hiding the extension. Atrium does the same with exceptions that reach it.

### Retry (commit `c9343bf`)

The work in `2f49607` was checked against criteria 1 to 6, then this group's test files were run one at a time. They were not run as a suite. Two test bugs were fixed:

- **`ScreenRegistrationsTest`, production case.** The test left the app in `production`, so the teardown's migration rollback asked for confirmation and the test errored. A file-level `afterEach` now sets the environment back to `testing`, even when an assertion fails partway through.
- **`SwitchableRedirectDomainPolicy` (throwing policy).** `$throws` also fired for Cortex's own sidebar item, which asks `viewAny` of the class with no owner. The Atrium layout renders that item, so the host page answered 500 outside the closure under test. The fixture now throws only for the owned check (`$owner !== null`), which is the case criterion 3 describes.

Results on this branch alone:

- `ScreenRegistrationsTest` (16), `StylesTest` (2) and `NoRosterReferenceTest` (1) pass.
- `OwnedRedirectDomainsTest`, `VisibilityTest` and group 2's `TestHostScreenTest` fail with `Call to undefined method CortexPlugin::historyNavItem()`. Atrium PR #16 removed that method, and dropping the call is task 4.4 in `src/Atrium/CortexPlugin.php`, which group 4 owns. With the call removed locally (not committed), all three pass: 13, 20 and 10 tests. So they go green once groups 3 and 4 are merged together.

Pint passes on both changed files.

## Group 4 (salvaged)

Declare Cortex's own screens and remove history UI (criteria 10 to 14). Branch `ring-master/2026-10-29-2038-register-roster-redirect-domains--g4`, commit `1f6a976`.

### What was found

The build agent ended without a result and without committing. There was no `index.lock` and no merge or rebase in progress. The worktree held the whole group's work, uncommitted: the deletion of `AuditTrailTest.php` was staged, everything else was unstaged or untracked. The last files edited were the README and `AGENTS.md` (the docs task), so the agent stopped at the end of 4.5, before running the suite. The work was checked against tasks 4.1 to 4.5:

- **4.1 tests:**
  - `DeclaredScreensTest`: a helper-level check that both kinds have the standard slots, only the `overview` tab (the default) and no `history` slot. Then, over all 11 screens, three datasets: a tab and a section in every slot rendered (the other kind's slot and `history` never render); the tab opened through `?tab=`, with only `header.actions` kept; no extension markup while nothing is registered. Two more cases: the run page after a run, and a configured redirect domain getting no `row.actions` section.
  - `ScreenAccessClosureTest`: a `view` section on `cortex.virtual-agent` follows the fixture policy. A `viewAny` section on `cortex.virtual-agents` is asked of the class, and a row section of its row. A `permission()` section on `cortex.run` never shows, even with `Gate::before` allowing everything.
  - `ScreenLayoutTest`: the aside landmark is absent with nothing in `aside` and with only hidden sections, and present after the content with a visible one. `assertSeeInOrder` places `header.actions` after the page's own actions (and in the header of a page with none of its own), `before`/`after` around the content, and `row.actions` in the row's last `td`, after its own controls. An open tab hides the page content, `before`, `aside` and `after` while `header.actions` stays, on a detail screen and on a list.
  - `NoHistoryUiTest`: no nav item routes to `atrium.history.show`; no `audit-trail` on the pages that showed it; a grep of `resources` (Boost included), `src`, `docs/agent/standards`, `README.md` and `CLAUDE.md` for `audit-trail` and `historyNavItem`.
- **4.2:** `src/Atrium/Screens.php`, with `declare()`. It builds `Screen::standard()` with the record, the `overview` default tab and the criterion 11 closure: a null record and a null subject deny. All 11 screens are declared and resolved after the controller's own authorization, and each passes `resolved` to its view. On `run.store` the screen resolves before the agent runs, so a `?tab=` the viewer may not open refuses the request without calling the agent.
- **4.3:** all 10 views (the virtual-agent form serves both `cortex.virtual-agent-create` and `cortex.virtual-agent`) render `screen-tabs`, `screen-tab-panel` and every slot of their kind, laid out like the group 2 test host. Pages with no header actions of their own add the actions slot only while `header.actions` has sections, so their markup is unchanged with nothing registered. On the redirect-domains list only stored rows get `row.actions`.
- **4.4:** the three `audit-trail` calls are gone and `historyNavItem()` is dropped from `CortexPlugin::navigation()`. `AuditTrailTest.php` is deleted. In `HistoryNavigationTest`, `links its own audit log from its sidebar group` became `links no history page from its sidebar group`, with the criterion 13 assertion.
- **4.5:** `CLAUDE.md`, the README's audit-trail paragraph and the Boost skill no longer mention audit-trail or history navigation; they now describe the screen declarations. The local standard's "History" section is replaced by "Declared screens", covering keys, standard slots, the access closure, row models, layout, and no history or audit components.

### What was committed

All of the above, in one commit. Pint passes on every changed PHP file, and PHPStan passes on `src/Atrium`. A grep of `resources`, `src`, `README.md`, `AGENTS.md` and `docs/agent/standards` for `audit-trail` or `historyNavItem` finds nothing. The Atrium API the work uses exists in the installed registry branch: `Screen::standard`, `defaultTab`, `permits`, `ResolvedScreen::$openRegisteredTab`, `sections()`, the `screen-*` components, and `NavItem::$route`.

Paths outside the group's file list:

- `AGENTS.md`: `CLAUDE.md` is a symlink to it, so this is where the `CLAUDE.md` edit lives. A generated Ring Master line ("Keep this checkout on `main`...") had also appeared in its `ring-master` block. It is not part of this group, so it was removed before committing; `ringside sync` adds it back.
- `tests/Feature/Ui/CortexPluginTest.php`: `contributes navigation for every section` lists the nav labels, and listed `Audit log`. Dropping `historyNavItem()` (criterion 13) forces dropping that label. Criterion 14's list of forced changes does not name this test, so the PR must list it.
- `tests/Fixtures/views/screen-probe.blade.php`: the view the new tests' tabs and sections render (`probe:<key>`).

### What is left

- 4.6: run `vendor/bin/pest`. Following the prompt, the suite was not run here; the gate runs it.
- Group 5 must keep the README free of `audit-trail` and `historyNavItem`, because `NoHistoryUiTest` greps it. The CHANGELOG is not grepped.

## Group 4

Retry for task 4.6 (criteria 10 to 14). Branch `ring-master/2026-10-29-2038-register-roster-redirect-domains--g4`, commit `a08a977` on top of the salvaged `1f6a976`.

### Files

- `tests/Feature/Ui/ScreenAccessClosureTest.php`: `asks a viewAny section on the virtual agents list of the class` now calls `app()->forgetScopedInstances()` before its page request. The test first calls `ScreenExtensions::resolve()` directly for a viewer with no abilities. Atrium's scoped `ScreenVisibility` keeps answers per request and per record, not per viewer, so that denial carried into the next request and hid the section. The row-section test in the same file already did this. The code needed no change.

### Tests

The full suite was not run here, as the prompt asks; the gate runs it. Run on this branch alone:

- `DeclaredScreensTest`: 37 pass.
- `ScreenAccessClosureTest`, `ScreenLayoutTest`, `NoHistoryUiTest`, `HistoryNavigationTest` and `CortexPluginTest`: 24 of 25 passed before the fix and 25 after it.
- All of `tests/Feature/Ui`: 170 pass. This covers the existing UI tests for every view this group rewrote.

Pint passes on the changed test.

### Deviations

None beyond the salvaged section above.

## Integrate wave 1 (groups 3 and 4)

Groups 3 and 4 were merged with `--no-ff`, group 3 first. Neither merge had a conflict: the groups touch disjoint files.

`composer test:unit` then had 3 errors, all `Tab [cortex.mcp] is already registered for [roster.organization].` They came from existing tests that boot `CortexServiceProvider` a second time. Group 3 registers the redirect-domain screens from `boot()` (criterion 2), and Atrium refuses a duplicate key in `testing`. Both groups' tests had passed on their own branches, but those branches did not run the full suite. The fix is in the tests only, so the code is unchanged. Each of these tests now calls `app(ScreenExtensions::class)->flush()` before booting again, the same way `ScreenRegistrationsTest` does. Their assertions are unchanged.

Existing tests changed here, which the PR must list under criterion 14:

- `tests/Feature/Mcp/McpRegistrationTest.php`: `registers the web transport when enabled` and `registers the local transport when enabled`
- `tests/Feature/Mcp/ServerToolOverrideTest.php`: `servers that are not overridable (AC 12)` → `ignores a cortex row with tools on the registered cortex route`

Result: `composer test:unit` passes, 808 tests and 3259 assertions. Pint passes on both changed files.
