# Tasks: Register Roster redirect domains

## Group 1: Owner-decided policy and owner-aware UI controller
package: Cortex
depends on: none
files: src/Domains/RedirectDomain/Policies/RedirectDomainPolicy.php, src/Atrium/Http/Controllers/RedirectDomainUiController.php, tests/Feature/OwnedRedirectDomainPolicyTest.php, tests/Feature/Ui/RedirectDomainOwnerControllerTest.php, tests/Feature/RedirectDomainsTest.php, tests/Fixtures/OwnerPolicyFixture.php
criteria: 7, 8, 14

- [x] 1.1 Write tests: OwnedRedirectDomainPolicyTest (guest, user without owner update, user with it, null-owner unchanged, deleted owner, over JSON, MCP and Gate) and RedirectDomainOwnerControllerTest (owner types, partial owner, unknown owner, owner-scoped destroy, same-origin redirect and the lookalike, userinfo, scheme-mismatch and missing-referrer fallbacks) per criteria 7 and 8.
- [x] 1.2 Change RedirectDomainPolicy create and delete for owned domains to `$user !== null && $user->can('update', $owner)` (deleted owner denied, null owner unchanged) and update its docblock.
- [x] 1.3 Add optional owner_type/owner_id to RedirectDomainUiController store and destroy (ownerRules validation, RedirectDomains::owner(), ownedBy scope, create/delete authorization, same-origin url()->previous() else atrium.cortex.redirect-domains.index).
- [x] 1.4 Sign in a user before the owned calls in RedirectDomainsTest `manages domains over the json api` and `creates and deletes domains over mcp with parity to the http payload` only, leaving their assertions unchanged.
- [x] 1.5 Run vendor/bin/pest; all green

## Group 2: Composer, arch rule, test host and the no-Atrium proof
package: Cortex
depends on: none
files: composer.json, tests/ArchTest.php, tests/TestCase.php, tests/Pest.php, tests/Feature/ComposerTest.php, workbench/app/Http/Controllers/TestHostScreenController.php, workbench/app/Models/Organization.php, workbench/app/Policies/OrganizationPolicy.php, workbench/app/Policies/UserPolicy.php, workbench/database/migrations/, workbench/resources/views/test-host.blade.php, phpunit.xml.dist, phpunit.without-atrium.xml, tests/WithoutAtrium/, .github/workflows/tests.yml
criteria: 9, 14, 15, 16, 17

- [x] 2.1 Write tests: ComposerTest (require only keystone among refactor-circus, atrium in suggest and require-dev, atrium conflict starting with `<`, no roster), the WithoutAtrium suite assertions of criterion 17, and a test-host smoke test that the workbench screen renders for roster.organization, roster.user and acme.account.
- [x] 2.2 Move refactor-circus/atrium from require to require-dev, add it to suggest with a description, and add a conflict on Atrium older than the screen-registry release (`<1.0.0` if Atrium is untagged, recorded in the PR).
- [x] 2.3 Add the arch rule `only the Atrium layer uses Atrium` to tests/ArchTest.php.
- [x] 2.4 Build the workbench test host (TestHostScreenController, Organization model and migration, OrganizationPolicy and UserPolicy with a deny flag, test-host view with screen-tabs, screen-tab-panel and screen-sections) and wire it plus ResetsScreenExtensions into tests/TestCase.php and tests/Pest.php.
- [x] 2.5 Add phpunit.without-atrium.xml, tests/WithoutAtrium/bootstrap.php (removes the Atrium PSR-4 prefix and throws if findFile still resolves) and WithoutAtriumTestCase, exclude it from phpunit.xml.dist, add the `test:without-atrium` composer script called by `composer test` after `@test:unit`, and add the `without-atrium` CI job.
- [x] 2.6 Run vendor/bin/pest; all green

## Group 3: Redirect domains on other packages' screens
package: Cortex
depends on: 1, 2
files: config/cortex.php, src/Atrium/ScreenRegistrations.php, src/CortexServiceProvider.php, resources/views/ui/screens/redirect-domains.blade.php, lang/en/cortex.php, tests/Feature/Ui/ScreenRegistrationsTest.php, tests/Feature/Ui/OwnedRedirectDomainsTest.php, tests/Feature/Ui/VisibilityTest.php, tests/Feature/Ui/StylesTest.php, tests/Feature/NoRosterReferenceTest.php, docs/agent/standards-feedback.md
criteria: 1, 2, 3, 4, 5, 6

- [ ] 3.1 Write tests: ScreenRegistrationsTest (default map, absent-key fallback, empty list, custom list, ui disabled, null/empty/bogus-slot throw in testing, production reports one and registers the valid entry), OwnedRedirectDomainsTest and VisibilityTest per criterion 6, the query-count and throwing-policy cases of criterion 3, the src grep for `roster`, and StylesTest coverage of the new view.
- [ ] 3.2 Add `atrium.redirect_domains.screens` with its banner and default map to config/cortex.php.
- [ ] 3.3 Implement ScreenRegistrations::register() (per-entry try, throw in local/testing, report and skip elsewhere) and call it from a private registerAtriumScreens() in CortexServiceProvider behind the registerAtriumPlugin() guard, and add the standards-feedback row for throwing InvalidArgumentException.
- [ ] 3.4 Implement the shared authorize closure (cheap early returns, caught policy exceptions) and resolver, and the cortex::ui.screens.redirect-domains view with the hint, add form, owned table, remove buttons and empty state, plus the `mcp` and `owned_redirect_domains_hint` lang keys.
- [ ] 3.5 Run vendor/bin/pest; all green

## Group 4: Declare Cortex's own screens and remove history UI
package: Cortex
depends on: 1, 2
files: src/Atrium/Screens.php, src/Atrium/CortexPlugin.php, src/Atrium/Http/Controllers/ConcreteAgentUiController.php, src/Atrium/Http/Controllers/McpInstructionUiController.php, src/Atrium/Http/Controllers/RedirectDomainUiController.php, src/Atrium/Http/Controllers/RunAgentUiController.php, src/Atrium/Http/Controllers/ServerUiController.php, src/Atrium/Http/Controllers/ToolDescriptionUiController.php, src/Atrium/Http/Controllers/ToolUiController.php, src/Atrium/Http/Controllers/VirtualAgentUiController.php, resources/views/ui/virtual-agents/index.blade.php, resources/views/ui/virtual-agents/form.blade.php, resources/views/ui/concrete-agents/index.blade.php, resources/views/ui/concrete-agents/show.blade.php, resources/views/ui/run.blade.php, resources/views/ui/tools/index.blade.php, resources/views/ui/tools/description.blade.php, resources/views/ui/servers/index.blade.php, resources/views/ui/servers/instructions.blade.php, resources/views/ui/redirect-domains/index.blade.php, resources/views/ui/partials/override.blade.php, tests/Feature/Ui/DeclaredScreensTest.php, tests/Feature/Ui/ScreenAccessClosureTest.php, tests/Feature/Ui/ScreenLayoutTest.php, tests/Feature/Ui/AuditTrailTest.php, tests/Feature/HistoryNavigationTest.php, tests/Feature/NoHistoryUiTest.php, CLAUDE.md, README.md, resources/boost/, docs/agent/standards/cortex/atrium-screens.md
criteria: 10, 11, 12, 13, 14

- [ ] 4.1 Write tests: DeclaredScreensTest dataset over the 11 screens (every standard slot, tab via ?tab=, per-kind leakage, no history slot, no row slot on configured redirect-domain rows), ScreenAccessClosureTest (view, viewAny, run never), ScreenLayoutTest (aside landmark, assertSeeInOrder for header.actions and row.actions, open tab hides page content), and NoHistoryUiTest (no atrium.history.show nav item, grep for audit-trail and historyNavItem).
- [ ] 4.2 Add Screens::declare() with the criterion 11 access closure and declare and resolve each of the 11 screens in their controllers, passing `resolved` to the views.
- [ ] 4.3 Render screen-tabs, screen-tab-panel and the standard slot sections in every Cortex view per Atrium criterion 15, leaving markup unchanged when nothing is registered.
- [ ] 4.4 Remove the audit-trail calls from ui/virtual-agents/index, ui/virtual-agents/form and ui/partials/override, drop historyNavItem() from CortexPlugin::navigation(), delete tests/Feature/Ui/AuditTrailTest.php and replace `links its own audit log from its sidebar group` with the criterion 13 assertion.
- [ ] 4.5 Drop audit-trail and history-nav text from CLAUDE.md, README.md (the audit-trail paragraph) and resources/boost, and replace the History section of docs/agent/standards/cortex/atrium-screens.md with the screen-declaration rule.
- [ ] 4.6 Run vendor/bin/pest; all green

## Group 5: README and CHANGELOG
package: Cortex
depends on: 3, 4
files: README.md, CHANGELOG.md, tests/Feature/DocsTest.php
criteria: 18, 19

- [ ] 5.1 Write tests in DocsTest for the README heading and phrases and the CHANGELOG keywords listed in criterion 18.
- [ ] 5.2 Add the README "Redirect domains on other screens" section (config, keys, visibility, owner update policy, Atrium is suggested, conflict, fail-fast in local/testing and report-and-skip in production, CI lane as the authoritative proof).
- [ ] 5.3 Add the CHANGELOG upgrade note covering the seven topics of criterion 18, including the owned-domain policy change and the Atrium conflict.
- [ ] 5.4 Run composer test (including test:without-atrium) and vendor/bin/pest; all green
