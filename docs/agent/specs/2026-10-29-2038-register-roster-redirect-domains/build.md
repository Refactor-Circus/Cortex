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
