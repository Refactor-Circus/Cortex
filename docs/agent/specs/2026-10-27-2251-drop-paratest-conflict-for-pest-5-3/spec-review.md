# Spec review

## Round 1

Changes made to spec.md. The two open questions (may the fallback raise pest/phpunit constraints; how "resolves on every CI lane" is verified before merge) were left open and not decided.

- Goal: named the lanes from `.github/workflows/tests.yml` (PHP 8.5, Laravel 13.*, prefer-lowest and prefer-stable) and said what is locally verifiable versus confirmed on the PR. Why: the goal was unverifiable by the old criteria.
- Criterion 3 (new): resolution must succeed under both `prefer-lowest` and `prefer-stable`, run as the workflow runs them, optionally in a scratch copy; prefer-lowest must not land on Pest 5.3.1. Why: the workflow runs both, and 5.3.1 is the known-bad floor. How the check is run in practice is left to the verifier, so the "how verified" question stays open.
- Criteria 4 and 5: pass condition stated as exit code 0; added rerun and non-parallel comparison to tell a paratest regression from a real failure, and a double `--parallel` run for flakiness.
- Criterion 6: diff check stated as a `git diff --name-only` subset. Finding said `composer.lock` is tracked, but `.gitignore` line 6 ignores it, so the spec now says it is never in the diff.
- Criterion 7 (new): the PR's `P8.5 / L13.*` CI checks are green; PR #8 may still need a rebase or re-run.
- Criterion 8: PR body must contain `Refs #13`, not `Closes`, because this PR only unblocks PR #8.
- Design: prefer `composer update pestphp/pest brianium/paratest --with-dependencies` over a full update; fallback bounded to the minimum pest/phpunit versions verified with `composer why-not`, and stop and report if another package's constraint must change. Whether the fallback is allowed at all remains the open question. Added a note that consumers are unaffected.
- Risks: implementer must read PR #7 and its commit message, record the reason in the PR description, and check the symptom does not reappear.
- Out of scope: stated that the PR does not close #13; removed the mention of lock changes.
