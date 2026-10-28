# Spec review

## Round 1

Changes to spec.md, and why:

- Acceptance criteria extended to six. Criterion 1 now lists the evidence of the rebase (head SHA change, merge-base with main, run id) and notes Dependabot's reduced token; an unresponsive or closed PR is recorded and handled per the open question.
- Criterion 2 now requires the run URL, per-job results, an unconfirmed-race statement, and an unchanged one-line PR diff.
- Criterion 3 now requires reproducing or explaining the cause first, names the allowed files, defines the pass condition (all matrix jobs green, no "Failed to open stream ... packages.php" in the PHPStan log), and says what to do when a push is refused. Run counts and fix location are left to the open questions.
- Criterion 4 now names the paratest failure signature and ends with a blocked release and a resume note naming the campaign PR.
- New criterion 5 defines the end state for any other failure: no change, record on #13, release as failed.
- Criterion 6 (was 5) states the file boundary, checkable with `git diff --name-only`.
- Out of scope now excludes composer.json, other workflows, matrix changes and other Dependabot PRs.
- Risks now cover Dependabot ceasing to manage the branch after a human push, the `workflow` token scope, and a closed or superseded PR.
- Left unchanged on purpose: the four open questions (fix location, green run count for the P8.5 job, rebase fallback, full-matrix run count).

## Pass 2: recheck of D-003, D-004, D-005

All three decisions had landed in the Addendum. The body still contradicted them, so spec.md was changed:

- Criterion 1: manual rebase when Dependabot ignores the comment (D-005); for a manual rebase, the merge-base evidence is checked against the `main` tip at push time; a closed or superseded PR is recorded on #13 with no other change.
- Criterion 2: two consecutive green runs on the same head (D-004), with both run URLs on #13; a failed re-run is handled by criterion 3, 4 or 5.
- Criterion 3: the fix goes in a separate PR against `main` (D-003); the job is released as blocked with a resume note naming that PR, since merging it is the user's call; on resume, PR #8 is rebased and must pass twice.
- Criterion 6: checked per PR (PR #8 = one-line bump; fix PR = `tests.yml` and/or the PHPStan config).
- Risks: updated for two green runs, the stale branch after a manual rebase, and the `workflow` token scope on both pushes.
- Open questions: none.
