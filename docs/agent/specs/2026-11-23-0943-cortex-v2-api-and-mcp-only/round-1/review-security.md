VERDICT: PASS

Security review, round 1. No findings at or above minor.

- LIKE input escaped with an explicit escape char; column names fixed; q validated (required, string, max 255).
- Search and runnable-agents check viewAny in authorize() (HTTP and MCP) and filter per item through Gate::forUser (view/run).
- Settings summary asks no policy by spec (AC 4); it exposes provider and model names plus counts only, no secrets.
- Scramble is dev-only and loaded by the workbench only; no docs UI route ships with the package.
- No views, Blade directives or published views remain.
