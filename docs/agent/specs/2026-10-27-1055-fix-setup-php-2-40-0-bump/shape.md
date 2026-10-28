# Fix CI failure on setup-php 2.40.0 bump — Shaping Notes

Ticket: https://github.com/Refactor-Circus/Cortex/issues/13

## Scope

Dependabot PR #8 (`shivammathur/setup-php` 2.37.2 → 2.40.0, one line in `.github/workflows/tests.yml`) has one red job: `P8.5 / L13.* / prefer-stable / ubuntu-latest`. Outcome: PR #8's checks go green. Out of scope: merging PR #8.

## Decisions

- D-001: GitHub issue created (answered in RingMaster).
- D-002: Rebase PR #8 and re-run first; change only the workflow if it still fails. If the rebased run fails on composer resolving paratest, wait for the campaign PR that drops Cortex's paratest conflict instead of fixing it here.

## Context

- The failing step is PHPStan, not setup-php: "Laravel framework bootstrap failed ... require(vendor/orchestra/testbench-core/laravel/bootstrap/cache/packages.php): Failed to open stream" in a parallel worker (run 38800233800, 2026-10-15).
- The other three jobs of that run (prefer-lowest on ubuntu and windows, prefer-stable on windows) passed.
- The PR run predates 2026-10-27; every `tests.yml` run on main and on the later branches since passed (latest on main 2026-10-27). PR #8 has not been re-run on current main.
- Leading hypothesis: a race creating Testbench's `bootstrap/cache/packages.php` across PHPStan parallel workers, not a setup-php regression. To confirm: rebase PR #8 (`@dependabot rebase`) and re-run; if it stays red, reproduce locally with `composer analyse`.
- The fix is a change to the PR branch or a workflow/PHPStan config tweak (for example, warm the Testbench package cache before PHPStan), not a Cortex source change.

## Standards

None of the local standards govern CI workflow files. The compat skill (`package-compatibility`) applies for the matrix.
