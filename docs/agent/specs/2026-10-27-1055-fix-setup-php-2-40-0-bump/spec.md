# Fix CI failure on setup-php 2.40.0 bump

Ticket: https://github.com/Refactor-Circus/Cortex/issues/13

## Goal

Dependabot PR #8 (`shivammathur/setup-php` 2.37.2 → 2.40.0) goes green, so a maintainer can merge it. Merging is not part of this job.

## Acceptance criteria

1. PR #8's branch is rebased onto current `main` (`@dependabot rebase`), and `tests.yml` runs on the rebased head. Evidence, recorded on issue #13: the PR head SHA changed, its merge-base equals the `main` tip when the comment was posted, and the run id of the `tests.yml` run on that head. The run executes under Dependabot's reduced token and secrets. If Dependabot ignores the comment, record that on #13 and rebase PR #8's branch manually onto `main` and push it (D-005); if it is closed or superseded, record that on #13 and make no other change. When the rebase is manual there is no comment, so the merge-base evidence is measured against the `main` tip at push time.
2. If every job of the run passes, including `P8.5 / L13.* / prefer-stable / ubuntu-latest`, and a single re-run of the workflow on the same head also passes every job (two consecutive green runs, D-004), no file changes: the PR diff is still the one-line setup-php bump, and the result is noted on issue #13 (D-002). The note includes both run URLs, the per-job results, and a statement that the race hypothesis is unconfirmed. If the re-run fails, treat it as the error that run shows (criterion 3, 4 or 5).
3. If the PHPStan bootstrap error ("Failed to open stream ... bootstrap/cache/packages.php") still occurs, first reproduce it locally (`composer analyse`) or explain the cause from the log, and record the cause on #13. Only then is a change made, and only to `.github/workflows/tests.yml` or the repo's PHPStan config file. The change goes in a separate PR against `main`, never onto Dependabot's branch (D-003). Merging that PR is the user's call: release this job as blocked with a resume note naming the fix PR, and on resume rebase PR #8 onto the merged `main`. Pass condition: the `tests.yml` run on PR #8's rebased head has every matrix job green, including `P8.5 / L13.* / prefer-stable / ubuntu-latest`, the PHPStan step log has no "Failed to open stream ... packages.php" line, and a single re-run on the same head is also fully green (D-004). If a push is refused (for example a token without the `workflow` scope, or a protected branch), release the job with that reason and do not retry blindly.
4. If the rebased run fails because composer cannot resolve paratest (identified by a composer "Your requirements could not be resolved" error naming paratest in the install step, as opposed to a prefer-lowest/prefer-stable difference), no fix is made here. Record it on #13, link the blocking campaign PR, and release the job as blocked (not done) with a resume note naming that PR (D-002).
5. If the rebased run fails for any other cause (a real setup-php 2.40.0 regression, or a different job failing), make no change: record the failing step and log excerpt on #13 and release the job as failed.
6. Checked per PR with `git diff --name-only` against `main`: PR #8 touches only `.github/workflows/tests.yml` (the setup-php bump). If criterion 3 applies, the separate fix PR touches only `.github/workflows/tests.yml` and/or the PHPStan config file. No file under `src/` changes in either.

## Design

No code design. The work is a rebase and a CI re-run; a workflow tweak (for example, warming the Testbench package cache before PHPStan) is a fallback only, per D-002. Order of fallbacks: rebase and re-run, then workflow/PHPStan config; paratest failures are out of this job.

## Out of scope

- Merging PR #8.
- Dropping Cortex's paratest conflict (separate campaign PR).
- Changes to Cortex source.
- `composer.json`, other workflows, matrix changes, and other Dependabot PRs.

## Risks and open questions

- The failure may be a flaky race, so green runs do not prove the cause; acceptance requires two consecutive green runs on the same head (D-004).
- A paratest resolution failure blocks this job until the campaign PR lands.
- A manual rebase pushed to PR #8's branch (D-005) stops Dependabot from rebasing or updating it, so the PR can go stale again. Pushing to `.github/workflows/*` also needs a token with the `workflow` scope, for the manual rebase and for the fix PR.
- If PR #8 is closed or superseded, note it on #13 and do no new work.

## Addendum 2026-10-28 (D-003, D-004, D-005)

These answers close the open questions above and override the criteria where they differ.

- Green proof (D-004): criteria 2 and 3 pass only with two consecutive green `tests.yml` runs on the same head (re-run once after the first green run); both run URLs go on #13.
- Fix location (D-003): if the PHPStan bootstrap error still occurs, the workflow/PHPStan config fix goes in a separate PR against `main`, not onto Dependabot's branch. Once it is merged, PR #8 is rebased onto it and criterion 3's pass condition is checked on that head. Criterion 6 then applies to each PR separately: PR #8 stays the one-line setup-php bump, the fix PR touches only `.github/workflows/tests.yml` and/or the PHPStan config file. Merging the fix PR is the user's call.
- No Dependabot rebase (D-005): if `@dependabot rebase` is ignored, rebase PR #8's branch manually onto `main` and push to the PR branch, then continue with criterion 1's evidence. If the PR is closed or superseded, the Risks note above still applies.
