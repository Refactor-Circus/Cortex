# Standards for Packaged UI SPA

`agent-os/standards/index.yml` contains no entries. The conventions below act as
the applied standards for this work.

---

## CLAUDE.md package conventions

- Use Laravel-native package APIs and the existing service provider shape before
  adding abstractions — the UI route registration mirrors `registerMcpServers()`,
  and assets ship through the pre-existing `cortex-assets` publish tag.
- Keep names, namespaces, publish tags, docs, and examples aligned with `refactor-circus/cortex`.
- Add only the files and dependencies needed for the behavior being implemented —
  frontend deps limited to vue, vue-router, vite, @vitejs/plugin-vue.
- Keep tests focused on observable package behavior: route registration, middleware,
  view rendering, config injection, publish tags, documentation promises.

## package-scaffold (local skill)

Applies to: config merge for the `ui` block, UI route registration in the service
provider, the Blade shell view, asset publishing, and workbench verification.

## package-testing (local skill)

Applies to: Pest 4 feature tests for the UI route (`tests/Feature/Http/UiTest.php`),
Testbench environment overrides for config-gated registration, 100% type coverage
on new PHP classes, strict_types arch rules.

## package-compatibility (local skill)

New PHP code must hold across PHP ^8.4 and Laravel 13 (illuminate/support
^13.15) with Testbench 11. No compiled-asset impact on the matrix.
