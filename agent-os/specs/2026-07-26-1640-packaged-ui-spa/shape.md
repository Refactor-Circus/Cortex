# Packaged UI SPA — Shaping Notes

## Scope

A packaged, prebuilt frontend dashboard for the Cortex REST API, shipped inside the
`refactor-circus/cortex` package (Horizon/Telescope model). Consumers enable it via config —
no npm work on their side. The frontend is headless with respect to the parent app:
it mounts at a configurable route, is gated by consumer-chosen middleware, and rides
the parent app's authentication whether that is web sessions, JWT, or OAuth tokens.

V1 surfaces: Prompts + versions (CRUD, publish), Agents (CRUD including tools /
prompt / sub-agent selectors), Agent runner playground, Tools read-only list.

## Decisions

- **Packaged prebuilt Vue 3 SPA** over npm headless lib or Blade/Livewire. Chosen
  for easiest Laravel integration: zero consumer build, no dependency coupling with
  the parent app (bundle is isolated), proven first-party pattern (Horizon).
  npm headless lib deferred as a possible later addition.
- **Auth adapter model**: `cortex.ui.auth.mode` = `session` (default; same-origin
  cookies + X-XSRF-TOKEN) or `token` (consumer registers a PHP `UiTokenResolver`,
  token injected into the Blade shell). `window.CortexToken` async hook as escape
  hatch for custom/refresh flows. Server-side API auth stays consumer-configured
  via `cortex.routes.middleware`.
- **Stable asset names** (`app.js` / `app.css`, single chunk, committed to repo)
  referenced via `asset('vendor/cortex/...')` with md5 cache-buster — no Vite
  manifest machinery.
- **GET-only catch-all** route at default path `cortex/ui` (disjoint from API
  prefix `cortex`), registered after API routes; gated on `cortex.ui.enabled`.
- **Lean dependencies**: vue + vue-router only; native fetch, no axios, no pinia,
  no UI kit; hand-written CSS.
- **JS unit tests skipped for v1**: SPA is a thin projection over an API that is
  already fully feature-tested; the PHP-owned contract (shell, config payload,
  auth injection) gets full Pest coverage instead.

## Context

- **Visuals:** None — minimal clean dashboard, sidebar nav, designed from API shape.
- **References:** Package internals studied (see references.md); Laravel Horizon as
  external packaging/asset-publishing pattern reference.
- **Product alignment:** N/A — `agent-os/product/` does not exist.

## Standards Applied

- `agent-os/standards/index.yml` is empty — no formal standards entries exist.
- CLAUDE.md package conventions and the `package-scaffold` / `package-testing`
  local skills serve as the applied standards (see standards.md).
