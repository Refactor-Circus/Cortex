Risk: high

## What changed
- **Redirect domains on host screens:** new `src/Atrium/ScreenRegistrations.php` registers a `cortex.mcp` tab or `cortex.redirect-domains` section on screens named in the new `cortex.atrium.redirect_domains.screens` config (default `roster.organization` tab, `roster.user` aside). It uses one shared view, `ui/screens/redirect-domains.blade.php`.
- **Controller:** `RedirectDomainUiController` `store` and `destroy` take optional `owner_type` and `owner_id`, and redirect back only to a same-origin referrer.
- **Authorization:** `RedirectDomainPolicy` `create` and `delete` for owned domains now ask `$user->can('update', $owner)`. This applies to the UI, the JSON API and MCP.
- **Own screens:** all 11 Cortex pages declare standard Atrium screens through `Atrium\Screens::declare()`, with the slots rendered in the views.
- **History and Atrium:** the audit-trail components and the history nav item are removed. Atrium moves to `require-dev` plus `suggest`, with a `conflict` on old versions.
- **No-Atrium proof:** a `tests/WithoutAtrium` suite, `phpunit.without-atrium.xml`, a composer script and a CI job.
- **Docs:** README, CHANGELOG, local standard, CLAUDE.md and the Boost skill.

## Why
Roster's MCP redirect-domain UI belongs in Cortex, placed on any package's screens through config, with Cortex never naming Roster. Who may manage an owner's domains is decided by the owner's own policy (D-022, D-023, D-027). Other decisions that shaped the work:
- D-020: prove the no-Atrium case both in process and in CI, and add the `conflict`.
- D-021: a malformed config entry throws in local and testing, and is reported and skipped in production.
- D-026: same-origin redirect after owned writes.
- D-028: no owner-type allow-list.
- D-024: one spec, merged together with Atrium, Keen and Roster.

## Look at
- `src/Domains/RedirectDomain/Policies/RedirectDomainPolicy.php:37`: the behaviour change for `create` and `delete`. Guests and users without `update` on the owner are now refused over the API and MCP. A missing owner row fails closed.
- `src/Atrium/ScreenRegistrations.php:137`: the `visible()` closure runs on every host render. Check that it queries nothing early and that a policy exception is swallowed.
- `src/Atrium/Http/Controllers/RedirectDomainUiController.php:117`: owner resolution (`owner()`), and the same-origin redirect in `back()` at line 140.
- `composer.json:45`: the Atrium `conflict` constraint and the `require` to `require-dev` move.
- `.github/workflows/tests.yml` and `tests/WithoutAtrium/bootstrap.php`: the CI lane is the authoritative no-Atrium proof.

## Tested
Gate round 2: PASS. phpstan and pint are clean, pest 828/828 passes with 100% coverage, and `test:without-atrium` 7/7 passes.
- 1, 2: `ScreenRegistrationsTest`, `NoRosterReferenceTest`
- 3: `VisibilityTest`
- 4, 6: `OwnedRedirectDomainsTest`
- 5: `StylesTest`
- 7: `RedirectDomainOwnerControllerTest`
- 8: `OwnedRedirectDomainPolicyTest`
- 9: `TestHostScreenTest`
- 10: `DeclaredScreensTest`
- 11: `ScreenAccessClosureTest`
- 12: `ScreenLayoutTest`
- 13: `NoHistoryUiTest`, `HistoryNavigationTest`
- 14: `AuditTrailTest` deleted; `RedirectDomainsTest` signs in a user
- 15, 16: `ComposerTest`, `ArchTest`
- 17: `WithoutAtriumTest`, plus the CI job (verified only by a workflow run)
- 18: `DocsTest`

## Risks
- This is a public behaviour change. Owner models without an `update` policy now deny owned create and delete for API and MCP callers (CHANGELOG notes it).
- Moving Atrium to `require-dev` breaks apps that relied on Cortex pulling it in.
- Atrium has no release tag. If none exists, the `conflict` uses `<1.0.0`, so check the constraint at merge time.
- Production: a malformed screens entry is reported once and skipped, so its UI is missing until someone acts on the report.
- A grouped merge with Atrium, Keen and Roster is required, or Roster's MCP tab disappears in between.
- A plain `InvalidArgumentException` departs from the `CortexException` convention, and a row was added to `standards-feedback.md`.
- The CI `without-atrium` lane has not been run here.
- Open findings: none (nits: 0).
