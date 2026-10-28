# Tasks: Fix CI failure on setup-php 2.40.0 bump

## Group 1: Rebase PR #8 and prove CI green
depends on: none
files: .github/workflows/tests.yml (only if criterion 3 applies, in a separate PR against main; never on PR #8's branch)
criteria: 1, 2, 3, 4, 5, 6

- [x] 1.1 Write the checks first: record the `main` tip SHA, PR #8's current head SHA, and the failing job's log (`gh run view <run> --log-failed`) on issue #13 as the baseline; define the pass check as `gh pr view 8 --json statusCheckRollup` showing every matrix job green and `git diff --name-only main...<PR #8 head>` listing only `.github/workflows/tests.yml`
- [x] 1.2 Check PR #8 is still open; if closed or superseded, note it on #13 and stop with no other change
- [x] 1.3 Comment `@dependabot rebase` on PR #8; if Dependabot ignores it, rebase the branch manually onto `main` and push it (D-005); a refused push releases the job with that reason
- [x] 1.4 Record on #13: new head SHA, merge-base equal to the `main` tip, and the run id of the `tests.yml` run on that head
- [x] 1.5 If every job is green, re-run the workflow once on the same head; if both runs are green, note both run URLs, per-job results and "race hypothesis unconfirmed" on #13, with no file changes (D-002, D-004)
- [x] 1.6 If the PHPStan "Failed to open stream ... packages.php" error recurs, reproduce with `composer analyse` or explain it from the log, record the cause on #13, open a separate fix PR against `main` touching only `.github/workflows/tests.yml` and/or the PHPStan config, then `release-task` as blocked with a resume note naming the fix PR (D-003); on resume, rebase PR #8 onto the merged `main` and repeat 1.4 and 1.5 (not needed: the error did not recur)
- [x] 1.7 If composer fails resolving paratest, record it on #13 with the blocking campaign PR link and `release-task` as blocked; for any other failure, record the failing step and log excerpt on #13 and `release-task` with `failed: true` (no change made) (not needed: both runs green)
- [x] 1.8 Verify criterion 6: `git diff --name-only main` per PR touches only the allowed files and nothing under `src/`; run `composer test` if any file changed (only `.github/workflows/tests.yml` on PR #8; no file changed, so no `composer test`)
