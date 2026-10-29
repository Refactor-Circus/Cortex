# Register Roster redirect domains — Shaping Notes

## Scope

Cortex owns its MCP redirect-domain UI on Roster's screens. Today Roster renders it when it detects Cortex (`Roster\Atrium\RedirectDomains::available()`). After this change, Cortex registers it through Atrium's screen extensions registry.

In scope:

- **Organization tab.** Register an `mcp` tab on `roster.organization` (label "MCP", icon `globe-alt`) that lists, adds and removes the organization's redirect domains. Visible when `cortex.redirect_domains.enabled` is on and the viewer holds the permission (spec writing settles which: Roster's `roster.organizations.update` via the host check, or a Cortex permission).
- **User section.** Register a "Redirect domains" section on the `roster.user` overview slot.
- **Move from Roster:**
  - `RedirectDomainUiController` (113 lines)
  - its four routes, which become Cortex Atrium routes under Cortex's plugin, e.g. `atrium.cortex.redirect-domains.*`
  - `ui/partials/redirect-domains.blade.php`
  - `tests/Cortex/RedirectDomainsTest.php`

  These call Cortex's existing `Create`, `Delete` and `ListRedirectDomainsAction`.
- **Cortex's own screens.** Drop the inline `<x-atrium::audit-trail>` from `ui/virtual-agents/index`, `form` and `ui/partials/override`. Declare those screens' audited subjects for Keen.

## Decisions

- D-001: Cortex references Roster only by screen keys and the model classes it already supports through `RedirectDomains` (`Cortex/src/Domains/RedirectDomain/Services/RedirectDomains.php:78`). It adds no `class_exists()` checks for Roster UI. If Roster isn't installed, the registrations never render.
- D-002: open for spec review: whether Roster's organization and user models are looked up by the morph types already registered with Cortex's `RedirectDomains`, or by the screen's record.
- D-003: the organization tab registers as `cortex.mcp` (vendor-prefixed, Atrium D-008). Old `?tab=mcp` links fall back to the default tab.

## Context

- **Depends on:** Atrium `screen-extensions-registry`. Must merge with or before Roster `adopt-atrium-screen-registry`, which deletes Roster's copy.
- **References:** see `references.md`.

## Standards

- architecture/dependencies: the add-on registers into the host by key.
- architecture/public-api: new routes and registration keys.
- atrium/components: views use `x-atrium::*` only.
- http/routes, http/authorization: the moved routes keep their middleware and policy checks.
- testing/test-layers: the moved feature test runs against Roster as a dev dependency.

## Addendum (2026-10-29): every screen declares slots and tab bar

User decision (Atrium D-013): every Atrium screen in this package declares itself with `Screen::make("{package}.{screen}")`, renders the Atrium tab bar (only visible once at least 2 tabs are visible), and declares the standard slots: `header.actions`, `before` and `after` on all screens; `aside` and `history` (when audited) on detail screens; the `row.actions` row slot on list screens. Screens without built-in tabs get a default `overview` tab. With nothing registered, every existing feature and browser test passes unchanged. A feature test per screen registers a tab and a section in each standard slot and asserts that they render.
Cortex screens (about 11): virtual agents index, create and edit; concrete agents index and show; run; tools and tool description; servers and server instructions; redirect domains.
