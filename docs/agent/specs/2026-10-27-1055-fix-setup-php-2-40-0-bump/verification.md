# Verification: Fix CI failure on setup-php 2.40.0 bump

Date: 2026-10-28 · Result: FAIL (local gate only; every acceptance criterion passes, and the job changed no repo file)

| Check | Result | Detail |
| --- | --- | --- |
| Criterion 1: PR #8 rebased onto `main`, `tests.yml` ran on the new head | pass | `@dependabot rebase` ignored; manual rebase per D-005. Head `2567b56` → `8a2bde7`, merge-base `c914866` (the `main` tip at push time), run 40637729378. Recorded on #13 (comment 6461508557) |
| Criterion 2: two consecutive green runs, no file changes, note on #13 | pass | Run 40637729378 attempts 1 and 2: all 4 jobs success, including `P8.5 - L13.* - prefer-stable - ubuntu-latest`; both URLs, per-job table and "race hypothesis unconfirmed" on #13 |
| Criterion 3: PHPStan bootstrap error handling | pass (not triggered) | No "Failed to open stream ... packages.php" line in attempt 1's log; no fix PR needed |
| Criterion 4: paratest resolution failure | pass (not triggered) | Original failure was the paratest conflict, fixed on `main` by #14; the rebased runs resolve |
| Criterion 5: other failure | pass (not triggered) | Both runs green |
| Criterion 6: PR #8 touches only `.github/workflows/tests.yml`, nothing under `src/` | pass | `git diff --name-only main...8a2bde7` → `.github/workflows/tests.yml` |
| Tasks | pass | Every box in `tasks.md` ticked |
| Full gate (`composer test`) | fail | 655/656 pass; 1 failure in `tests/Feature/Compat/KeenInCiTest.php`. Pre-existing: the working tree is `main` with no tracked changes. PHPStan and Pint pass |
| Standards drift (`vendor/bin/ringside check`) | fail | "Generated files are stale or missing. Run: vendor/bin/ringside sync". Pre-existing, unrelated to this job |

## Failures

`composer test`:

```
{"tool":"pest","result":"failed","tests":656,"passed":655,"failed":1,
 "failures":[{"test":"...KeenInCiTest::it keeps refactor-circus/keen in require-dev with a lock entry",
 "file":"tests/Feature/Compat/KeenInCiTest.php","line":93,
 "message":"Failed asserting that an array contains 'refactor-circus/keen'."}]}
```

Cause: the test reads the root `composer.lock`, which is gitignored (`.gitignore:6`) and stale in this checkout. It has no `refactor-circus/keen` entry, although `vendor/refactor-circus/keen` is installed as a symlink by the local (`composer.local.json`) install. CI makes a fresh lock and passes this test on both runs.

`vendor/bin/ringside check`:

```
ringside: 1 problem(s)
  Generated files are stale or missing. Run: vendor/bin/ringside sync
```

## Tasks to reopen

None. Neither failure comes from this job's work, which changed no repo file. They are local-environment problems on `main`. Whether that blocks `done` is questions.yml Q-001.
