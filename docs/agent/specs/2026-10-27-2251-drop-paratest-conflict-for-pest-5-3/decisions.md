# Decisions

## D-001: Ticket handling

- **Question:** Ticket draft "Drop brianium/paratest conflict so composer resolves with Pest 5.3": create a new GitHub issue as drafted, or use existing issue #13?
- **Choice:** Use existing issue (#13, "Fix CI failure on setup-php 2.40.0 bump (PR #8)").
- **Rationale:** None given. The recommendation was the existing issue, since #13 covers this CI failure.
- **Date:** 2026-10-28
- **Raised by:** ticket (answered in RingMaster)

## D-002: Standards for this change

- **Question:** Which standards apply to this composer.json-only change (drop the brianium/paratest conflict)?
- **Choice:** architecture/dependencies only.
- **Rationale:** None given. Matches the recommendation: only composer.json constraints change, no code or tests.
- **Date:** 2026-10-28
- **Raised by:** shape (answered in RingMaster)

## D-003: Raising pest/phpunit constraints if dropping the conflict is not enough

- **Question:** If dropping the conflict alone does not resolve, may the job raise the pestphp/pest or phpunit constraints in composer.json, or must it stop and report?
- **Choice:** Stop and report; do not change pest/phpunit constraints in this job.
- **Rationale:** None given. Matches the recommendation: criterion 6 and Out of scope keep the diff to removing the conflict.
- **Date:** 2026-10-28
- **Raised by:** spec-review (answered in RingMaster)

## D-004: Verifying "resolves on every CI lane"

- **Question:** How is 'resolves on every CI lane' verified before merge?
- **Choice:** Require the P8.5 / L13.* CI checks on the PR to be green.
- **Rationale:** None given. Matches the recommendation: tests.yml has a prefer-lowest/prefer-stable matrix that local checks do not cover.
- **Date:** 2026-10-28
- **Raised by:** spec-review (answered in RingMaster)

## D-005: Spec approval

- **Question:** Approve the spec as written (remove the brianium/paratest conflict; no pest/phpunit constraint changes; the PR's P8.5 / L13.* checks must be green)?
- **Choice:** Approve the spec.
- **Rationale:** None given. Matches the recommendation: D-003 and D-004 are reflected in spec.md.
- **Date:** 2026-10-28
- **Raised by:** approval (answered in RingMaster)

## D-006: Other packages still declare the paratest conflict

- **Question:** Dropping Cortex's brianium/paratest conflict is not enough: Atrium, Keen and PennantPlus (installed as dev-main) still declare the same conflict. How should this job proceed?
- **Choice:** Hold this job; open follow-up jobs in Atrium, Keen and PennantPlus to drop their conflict, then resume.
- **Rationale:** None given. Matches the recommendation: composer cannot resolve until those packages drop the conflict, and changing them belongs in their own jobs.
- **Date:** 2026-10-28
- **Raised by:** build (answered in RingMaster)
