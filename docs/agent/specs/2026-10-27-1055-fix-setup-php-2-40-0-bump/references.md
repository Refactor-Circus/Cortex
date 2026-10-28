# References

- `.github/workflows/tests.yml`: the only file PR #8 changes; the matrix is `P{php} / L{laravel} / {stability} / {os}`.
- Failed run: https://github.com/Refactor-Circus/Cortex/actions/runs/38800233800/job/116647049225 — PHPStan bootstrap fails on a missing Testbench `bootstrap/cache/packages.php`.
- Passing runs on main (for example 40451198391) show the same workflow green without the bump.
