# Drop brianium/paratest conflict so composer resolves with Pest 5.3 — Shaping Notes

## Scope

Change to `composer.json` (lock and CI config only if needed): `conflict` has `brianium/paratest: >=7.26.1` (added in PR #7). `pestphp/pest ^5.3.1` resolves to v5.3.2+, which needs paratest `^7.26.1`+, so the `P8.5 / L13.*` CI jobs cannot resolve. v5.3.1 conflicts with phpunit 13.4.5, so pinning to it is no way out.

Outcome: verify paratest 7.27.x works locally (`composer test:unit`, `vendor/bin/pest --parallel`), remove the conflict entry (raise pest/phpunit constraints if needed), PR against main, `composer test` green.

Out of scope: anything but `composer.json` (plus lock/CI config if needed).

## Decisions

- D-001: use existing issue #13 (https://github.com/Refactor-Circus/Cortex/issues/13) as the ticket instead of creating a new one.
- D-002: standards for this change are `architecture/dependencies` only.
- Note: #13 is titled "Fix CI failure on setup-php 2.40.0 bump (PR #8)" and asks for PR #8's checks to go green; this job removes the resolution failure behind that CI failure.

## Context

- `composer.json:165-167` holds the `conflict` entry; `composer.json:32` pins `pestphp/pest ^5.3.1`; the `test:unit` script runs `vendor/bin/pest --parallel`.
- Sibling specs in `docs/agent/specs/` (setup-php 2.40.0 bump) are about the same CI failure.
- No product docs alignment needed.

## Standards

- `architecture/dependencies` (confirmed, D-002): constraints in composer.json; the change adds no dependency, it only drops a conflict entry.
