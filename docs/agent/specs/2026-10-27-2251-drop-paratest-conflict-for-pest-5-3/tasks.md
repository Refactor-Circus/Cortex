# Tasks: Drop brianium/paratest conflict so composer resolves with Pest 5.3

## Group 1: Remove the paratest conflict and verify resolution
depends on: none
files: composer.json
criteria: 1, 2, 3, 4, 5, 6, 7, 8

- [ ] 1.1 Read PR #7 and its commit message for why the `brianium/paratest` conflict was added; note the reason for the PR description (Refs #13, not Closes; consumers only get looser constraints)
- [ ] 1.2 Record the failing baseline: `composer update` resolution fails while the conflict is present (criterion 2 check, before the change)
- [ ] 1.3 Delete the `brianium/paratest` entry from `conflict` in `composer.json`; remove the `conflict` key if it ends up empty (criterion 1)
- [ ] 1.4 Run `composer update pestphp/pest brianium/paratest --with-dependencies`; confirm pest >= 5.3.2 and paratest >= 7.26.1 are installed (criterion 2)
- [ ] 1.5 In a scratch copy of the repo, run the workflow's `composer require "laravel/framework:13.*" "orchestra/testbench:<matrix value>" --no-update`, then `composer update --prefer-lowest` and `composer update --prefer-stable`; under prefer-lowest pest must not be 5.3.1, otherwise stop and report with `composer why-not` output (D-003; do not change pest/phpunit constraints) (criterion 3)
- [ ] 1.6 Run `composer test`; all green (criterion 4)
- [ ] 1.7 Run `vendor/bin/pest --parallel` at least twice; on a failure rerun once and compare with `vendor/bin/pest`; a repeated parallel-only failure means stop and report, never re-add the conflict (criterion 5)
- [ ] 1.8 Check `git diff --name-only` is a subset of `composer.json` and `.github/workflows/*` (criterion 6)
- [ ] 1.9 Open the PR against `main` with `Refs #13`; the `P8.5 / L13.*` checks must be green before merge (criteria 7, 8)
