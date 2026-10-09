# Standards for Prompts, Tools, Agents (API + MCP)

`agent-os/standards/index.yml` is an empty stub at shaping time — no discovered standards to include.

Applicable conventions come from `CLAUDE.md`:

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep names, namespaces, Composer metadata, publish tags, docs, and examples aligned with `refactor-circus/cortex`.
- Add only files and dependencies needed for the behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Tests focus on observable behavior through public APIs, provider wiring, commands, routes, published resources, and documentation promises.

Quality gate for every task: `composer test` (PHPStan level 7, Pint, 100% type coverage, Pest parallel).
