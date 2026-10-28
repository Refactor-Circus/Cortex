# Decisions

## D-001

- Question: Create a GitHub issue for this fix (draft - Title - "Fix CI failure on setup-php 2.40.0 bump (PR #8)"; Problem - Dependabot PR #8 fails the P8.5 / L13.* / prefer-stable / ubuntu-latest job; Outcome - PR #8 checks go green, not merged; Out of scope - merging), or skip the ticket and fix the PR branch directly?
- Choice: Create the issue
- Rationale: none given (answered in RingMaster)
- Date: 2026-10-27
- Raised by: ticket

## D-002

- Question: Failing job is a PHPStan bootstrap error (missing Testbench bootstrap/cache/packages.php in a parallel worker), and PR #8's run is from 2026-10-15 while main has been green since. How should the job be fixed?
- Choice: Rebase PR #8 and re-run first, change only the workflow if it still fails
- Rationale: A campaign is also dropping Cortex's paratest conflict; if the rebased run fails on composer resolving paratest, wait for that PR rather than fixing it here. (answered in RingMaster)
- Date: 2026-10-28
- Raised by: shape

## D-003

- Question: If the rebased run still fails on the PHPStan bootstrap race, where should the workflow fix land?
- Choice: Open a separate PR against main with the fix, then rebase PR #8 onto it
- Rationale: none given (answered in RingMaster)
- Date: 2026-10-28
- Raised by: spec-review

## D-004

- Question: How many consecutive green runs of the previously failing P8.5 / L13.* / prefer-stable / ubuntu-latest job count as proof before the job reports 'no change needed'?
- Choice: Two consecutive green runs on the same head (re-run once)
- Rationale: none given (answered in RingMaster)
- Date: 2026-10-28
- Raised by: spec-review

## D-005

- Question: If Dependabot does not rebase PR #8 (comment ignored, PR closed or superseded), how should criterion 1 be satisfied?
- Choice: Rebase manually and push to the PR branch
- Rationale: none given (answered in RingMaster)
- Date: 2026-10-28
- Raised by: spec-review

## D-006

- Question: Approve the spec for Fix CI failure on Dependabot setup-php 2.40.0 bump as written (acceptance criteria 1-6 after the D-003..D-005 recheck)?
- Choice: Approve
- Rationale: none given (answered in RingMaster)
- Date: 2026-10-28
- Raised by: approval

## D-007

- Question: PR #8 is green on two consecutive CI runs with no repo file changes, but the local gate fails on main: KeenInCiTest reads a stale gitignored composer.lock, and ringside check reports stale generated files. Mark this job done, or hold it?
- Choice: Mark done and propose a follow-up task to fix the local gate drift
- Rationale: none given (answered in RingMaster)
- Date: 2026-10-28
- Raised by: build
