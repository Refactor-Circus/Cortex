# Decisions

Earlier D-ids are recorded in shape.md.

## D-020: Suite: proving the package runs without Atrium, and refusing an Atrium too old for the screen registry
- Choice: Prove it both ways: a WithoutAtrium suite inside `composer test`, and a CI lane with Atrium uninstalled. Every package that renders Atrium screens adds a composer `conflict` on refactor-circus/atrium versions older than the screen-extensions registry (Atrium PR #16).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-021: Suite: malformed integration config entries (screen maps, slots) at boot
- Choice: Throw InvalidArgumentException in local and testing. In production, report once, skip the entry and keep booting (same rule as Atrium D-022).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-022: Who may manage an owner's redirect domains (UI, API, MCP)?
- Choice: For a domain with an owner, the bundled policy's create/delete become `$user->can('update', $owner)`, so the owner's own policy decides. Domains without an owner are unchanged. This adds a criterion, tests (a guest, a user without update on the owner, the null-owner path unchanged) and a CHANGELOG line.
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user (2026-10-29)

## D-023: Q-1: Who may manage an owner's domains on a host screen? Cortex cannot name the host's permissions, such as Roster's `roster.organizations.update`.
- Choice: (c) (a), and the bundled policy's `create`/`delete` for a non-null owner become `$user->can('update', $owner)`, so the owner's own policy decides.
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-024: Should this spec stay one deliverable, or split into redirect-domain registration, declaration of Cortex's own screens, and the Atrium-suggest and history-removal work?
- Choice: Keep one spec (grouped merge with Atrium, Keen and Roster)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-025: Q-2: How should the no-Atrium proof run: in process only, in a CI lane only, or both?
- Choice: Both
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-026: Q-3: After UI store or destroy with an owner, where should the redirect go?
- Choice: `url()->previous()` when its host is the application's own, else Cortex's redirect-domains index
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-027: Q-1. Who may manage an owner's domains on a host screen, and through the POST routes? The bundled policy allows everyone (guests too, per Cortex's convention). Redirect domains are an OAuth redirect allow-list, and the hidden owner_type/owner_id fields are not tied to the host screen's authorization, so any user who reaches Cortex's routes could add a redirect domain for any owner.
- Choice: (c) (a), plus the bundled policy's create/delete for a non-null owner become $user->can('update', $owner), so the owner's own policy decides (also applies to the API and MCP)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-028: Should the owner types accepted by the UI routes be limited to an explicit allow-list in config, or to whatever RedirectDomains::owner() already resolves (any morph-map alias or FQCN of an Eloquent model)?
- Choice: Keep RedirectDomains::owner()'s current resolution and add tests that unsupported types are refused
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-029: Q-2. Run the no-Atrium proof in process only, in a CI lane only, or both?
- Choice: In-process suite and CI lane (as written)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-030: Q-2. How is 'runs without Atrium' proven? The spec does both, so the choice is settled only if confirmed.
- Choice: Both
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)
