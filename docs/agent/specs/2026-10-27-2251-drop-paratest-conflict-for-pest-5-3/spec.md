# Drop brianium/paratest conflict so composer resolves with Pest 5.3

Ticket: [#13](https://github.com/Refactor-Circus/Cortex/issues/13)

## Goal
Make `composer update` resolve on every CI lane by removing the `brianium/paratest` conflict that blocks `pestphp/pest ^5.3.1` (v5.3.2+ needs paratest `^7.26.1`). The lanes are the `tests.yml` matrix: PHP 8.5, Laravel 13.*, stability `prefer-lowest` and `prefer-stable`, on each OS in the matrix. This unblocks the red checks behind the setup-php 2.40.0 bump (PR #8).

Locally verifiable: resolution under both stabilities and the test suite. CI is the final confirmation on this PR's own checks (criterion 7).

## Acceptance criteria
1. `composer.json` has no `brianium/paratest` entry under `conflict`; if that leaves `conflict` empty, the key is removed.
2. `composer update` resolves without error on the local PHP version, installing `pestphp/pest` 5.3.2 or later and `brianium/paratest` 7.26.1 or later.
3. Resolution also succeeds under both CI stabilities, run the way the workflow runs them (`composer require "laravel/framework:13.*" "orchestra/testbench:<matrix value>" --no-update`, then `composer update --prefer-lowest ...` and `composer update --prefer-stable ...`). The verifier may use a scratch copy of the repo so the working tree and `vendor/` stay untouched. Under `prefer-lowest`, the resolved `pestphp/pest` must not be 5.3.1 (known to conflict with phpunit 13.4.5); if it is, stop and report (D-003); the floors in `composer.json` are not changed in this job.
4. `composer test` exits 0.
5. `vendor/bin/pest --parallel` (the `test:unit` script) exits 0 with the resolved paratest. If it fails, rerun once and compare with `vendor/bin/pest` (non-parallel): a failure that also happens non-parallel is a real failure, a failure only under `--parallel` that repeats is a paratest regression and triggers the stop-and-report path in Risks. Run `--parallel` at least twice to catch flakiness.
6. `git diff --name-only` is a subset of {`composer.json`, `.github/workflows/*`}. `composer.lock` is gitignored in this repo, so it is never part of the diff.
7. The PR's CI checks (every `P8.5 / L13.*` job) are green. PR #8 may still need a rebase or re-run once this merges; that is outside this PR.
8. The PR targets `main`, and its body contains `Refs #13` (not `Closes`): this PR unblocks PR #8's checks, and #13 is closed when PR #8's checks go green.

## Design
Delete the `"conflict": { "brianium/paratest": ">=7.26.1" }` block from `composer.json`. Add no new dependency (`architecture/dependencies`).

To keep the change narrow, resolve locally with `composer update pestphp/pest brianium/paratest --with-dependencies` rather than a full `composer update`, so unrelated dev dependencies do not move.

No fallback (D-003): if resolution still fails (including the `prefer-lowest` case in criterion 3), stop and report with the `composer why-not` output. Do not change the `pestphp/pest`, `phpunit/phpunit` or any other constraint in this job.

Consumers: `conflict` in a package's `composer.json` is inherited by apps that require it. Dropping the entry only loosens their constraints, so consumers are unaffected; say so in the PR description.

## Out of scope
- Changes to anything but `composer.json` (plus CI config if needed).
- The setup-php 2.40.0 bump itself (its own spec). This PR does not close #13; it only unblocks PR #8's checks.
- Pinning Pest to 5.3.1, which conflicts with phpunit 13.4.5.

## Risks and open questions
- Paratest 7.26.1+ may behave differently under `--parallel`; criterion 5 covers it.
- The conflict was added in PR #7 for a reason not recorded here. Before removing the entry, the implementer reads PR #7 and its commit message and records the reason in the PR description, then checks that the symptom does not reappear (criteria 4 and 5). If the suite fails under the newer paratest, the job must stop and report rather than re-add the conflict.

## Addendum 2026-10-28 (D-003, D-004)
- D-003: the Design fallback is not permitted. If dropping the conflict alone does not resolve (including criterion 3's `prefer-lowest` case), the job stops and reports; it never changes the `pestphp/pest` or `phpunit/phpunit` constraints. Design and criterion 3 now say so.
- D-004: "resolves on every CI lane" is verified by the PR's `P8.5 / L13.*` checks being green (criterion 7); local resolution alone is not enough to merge.
