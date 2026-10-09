# Cortex Packaged UI SPA — Implementation Plan

## Context

Cortex (`refactor-circus/cortex`) is a headless Laravel AI-orchestration package: Prompts (immutable versions + published pointer), Tools (registry), Agents (CRUD + run), exposed via 16 REST endpoints (`routes/cortex.php`, prefix `cortex`, JsonResource envelope, slug identifiers) and a parity MCP server. It has zero frontend today — `public/` holds only `.gitkeep`, the sole view is `placeholder.blade.php`, no package.json.

Goal: ship a **packaged, prebuilt Vue 3 SPA** (Horizon/Telescope model) so consumers get a dashboard with `composer require` + `vendor:publish` — no npm on their side — that registers into the parent app and rides the parent app's auth (session, JWT, or OAuth token).

Shaping decisions (user-confirmed):
- **Stack**: Vue 3 + vue-router + Vite; compiled bundle committed under package `public/`; shipped via the existing `cortex-assets` publish tag (`public/ → public_path('vendor/cortex')`, already wired in `CortexServiceProvider.php:55-57`).
- **Auth**: adapter model — `session` mode default (same-origin cookies + `X-XSRF-TOKEN`), `token` mode via a consumer-registered PHP resolver injected into the shell, plus a `window.CortexToken` async-hook escape hatch for custom/refresh flows.
- **Scope v1**: Prompts + versions (CRUD, publish), Agents (CRUD incl. tools/prompt/sub-agent selectors), Agent runner playground, Tools read-only list.
- **Visuals**: none — minimal clean dashboard, sidebar nav, hand-written CSS, no UI kit, no pinia, no axios (native fetch).
- JS unit tests skipped v1: SPA is a thin projection over an API already fully feature-tested in `tests/Feature/Http/`; PHP-owned contract (shell, config payload, auth injection) gets full Pest coverage.

## Architecture

- **UI route**: GET-only catch-all `cortex/ui/{view?}` (`->where('view', '.*')`), registered in a new `registerUiRoutes()` in `CortexServiceProvider::boot()` after `loadRoutesFrom()` (API wins registration order). Gated on `cortex.ui.enabled`. Default path `cortex/ui` is disjoint from API prefix `cortex` — no shadowing; GET-only means writes can never fall through.
- **Blade shell** `resources/views/app.blade.php` (replaces `placeholder.blade.php`): `<div id="cortex">`, `window.CortexConfig = @json(...)`, assets via `asset('vendor/cortex/app.js|app.css')` + `?v=` md5 cache-buster.
- **Stable asset names**: Vite outputs single-chunk `public/app.js` / `public/app.css` (`inlineDynamicImports`, `entryFileNames: 'app.js'`, `assetFileNames: 'app.[ext]'`, `emptyOutDir: false`, `base: ''`) — no manifest parsing, no chunk-URL problems when served from `/vendor/cortex`.
- **CortexConfig payload** (built by `UiController`): `apiBase` (`url(config('cortex.routes.prefix'))`), `basePath` (router history base), `auth.mode`, `auth.token` (token mode via resolver), `csrfToken`.

### New config block (`config/cortex.php`)

```php
'ui' => [
    'enabled' => true,
    'path' => 'cortex/ui',
    'middleware' => ['web'],
    'auth' => [
        'mode' => 'session',        // 'session' | 'token'
        'token_resolver' => null,   // invokable implementing UiTokenResolver
    ],
],
```

Controller reads nested keys with explicit fallbacks (`mergeConfigFrom` is shallow).

### Auth / CSRF resolution (documented in config comments + README)

- **Session mode** (default): client sends cookies + `X-XSRF-TOKEN` (from cookie, fallback `X-CSRF-TOKEN` from injected `csrfToken`). Recommended production pairing: `routes.middleware => ['web', 'auth']` and `ui.middleware => ['web', 'auth']` — `web` on the API makes CSRF verification work with the token the client already sends. Non-browser API consumers should instead use token mode + `auth:sanctum`.
- **Token mode**: `token_resolver` (e.g. returns Sanctum token for logged-in user); API middleware `['api', 'auth:sanctum']`; UI middleware `['web', 'auth']`. Token resolved once per page load; on 401 client awaits `window.CortexToken()` once and retries — documented limitation.

## Files

### PHP (new/changed)
- `config/cortex.php` — add `ui` block.
- `src/Contracts/UiTokenResolver.php` (new dir) — `public function resolve(Request $request): ?string;`.
- `src/Http/Controllers/UiController.php` — `final` single-action (matches existing controller style), builds payload + `assetVersion` (md5_file of package `public/app.js`, null pre-build); throws `RuntimeException` if resolver doesn't implement contract.
- `resources/views/app.blade.php` — shell; delete `placeholder.blade.php`; update `tests/Feature/ExampleTest.php:28` reference.
- `src/CortexServiceProvider.php` — `registerUiRoutes()` mirroring `registerMcpServers()` pattern; route name `cortex.ui`. No publish-tag changes needed.
- `tests/Feature/Http/UiTest.php` + `tests/Fixtures/FixedTokenResolver.php` (+ non-conforming fixture).

### JS (all new)
- `package.json` (private; deps vue@^3, vue-router@^4; devDeps vite@^7, @vitejs/plugin-vue; scripts build/watch), `package-lock.json` (committed), `vite.config.js`.
- `resources/js/`: `app.js`, `App.vue`, `router.js`, `config.js`; `api/client.js` (fetch wrapper, session/token adapters, `ApiError {status, message, errors}` from the standard 422 envelope), `api/prompts.js`, `api/agents.js`, `api/tools.js`; `components/` AppLayout (sidebar nav), Pagination (from API `meta`), FieldErrors (422 per-key), Alert, ConfirmButton, Spinner; `views/` prompts/{PromptsIndex,PromptForm,PromptShow}.vue, agents/{AgentsIndex,AgentForm}.vue, RunAgent.vue, ToolsIndex.vue.
- `resources/css/app.css` — variables, layout, tables, forms (~300 lines).
- Router: `/` → `/prompts`; `/prompts[,/create,/:slug,/:slug/edit]`; `/agents[,/create,/:slug/edit]`; `/run?agent=slug`; `/tools`. History base = `CortexConfig.basePath`.
- AgentForm selectors populated from GET /tools, /prompts, /agents; settings inputs limited to temperature/max_steps/max_tokens/top_p (server stays source of truth).

### Repo hygiene
- Commit `public/app.js` + `public/app.css`; `.gitignore` add `node_modules`; `.gitattributes`: `linguist-generated` for `/public/*.js|css`, `export-ignore` for `/resources/js`, `/resources/css`, `/vite.config.js`, `/package.json`, `/package-lock.json` (NOT `public/` — it's the dist payload).

## Tasks (ordered)

1. **Save spec docs** — `agent-os/specs/2026-07-26-1640-packaged-ui-spa/` with `plan.md` (this plan), `shape.md` (scope/decisions/context per shape-spec template), `standards.md` (index.yml empty — note CLAUDE.md conventions + package-testing/scaffold skills as applied standards), `references.md` (files studied: service provider, config, routes, Http/Request base, resources, McpRegistrationTest, testbench.yaml; Horizon as external packaging reference). No visuals.
2. **Config + contract** — `ui` config block, `UiTokenResolver` interface, config-default tests.
3. **Controller + shell + wiring (TDD)** — write `UiTest.php`: default registration (200, `cortex::app`, payload assertions), catch-all deep path, POST → 405/404, disabled → `Route::has('cortex.ui') === false` (use `#[DefineEnvironment]`; fallback TestCase subclass — `McpRegistrationTest`-style re-boot can't remove routes), middleware assertion via `gatherMiddleware()`, token mode (resolver injected / null / non-conforming throws). Implement `UiController`, `app.blade.php`, `registerUiRoutes()`. Green with `assetVersion === null` (no bundle yet).
4. **Frontend scaffold** — tooling + client + adapters + layout components; `npm run build` emits `public/app.js|css`; shell renders in workbench.
5. **Surfaces** — Tools → Prompts (+versions/publish; exercises pagination + 422 rendering first) → Agents → RunAgent playground.
6. **Committed build + publish test** — rebuild, commit bundle, hygiene files; publish-tag Pest test (`vendor:publish --tag=cortex-assets` lands `public_path('vendor/cortex/app.js')`; umbrella `cortex` tag includes it).
7. **Docs** — README "Dashboard UI" + "UI Authentication" sections (middleware pairings, resolver example, `window.CortexToken`, re-publish-on-upgrade + composer `post-update-cmd` hint); extend security callout to cover UI; dev-workflow notes; regenerate Boost skill via `package-generate-skill`.
8. **Full verification** (below).

Tasks 2→3 sequential; 4→5 sequential; 4 can start parallel to 3.

## Dev workflow (package author)

`npm install` once; then `npm run watch` + `composer serve`. Testbench `asset-publish` copies (not symlinks) — either re-run `composer serve` after bundle changes or one-time symlink `public/` into the workbench skeleton. `vite dev`/HMR out of scope v1.

## Verification

1. `composer test` — pint, larastan, type coverage 100% (new PHP fully typed, `strict_types`), Pest suite incl. `UiTest`.
2. `npm ci && npm run build && git status --porcelain public` — committed bundle reproducible/current.
3. `composer build` — workbench build; `public/vendor/cortex/app.js` present in skeleton.
4. `composer serve` walkthrough: `/cortex/ui` loads (no asset 404s); prompt create → version → publish → confirm via API; workbench-registered tool visible in Tools + Agent form; agent create with prompt/tool/sub-agent → run in playground shows text + usage; deep-link refresh serves shell; duplicate-slug and bad-settings 422s render field errors.
5. Toggles: `ui.enabled=false` → route absent; token mode with workbench resolver → token in `window.CortexConfig`.

## Risks

- Catch-all/API collision — eliminated (disjoint path, GET-only, API-first registration); noted for consumers customizing `ui.path`.
- Session CSRF on API writes — resolved via documented `['web','auth']` pairing; client always sends `X-XSRF-TOKEN` so both `api` and `web` API middleware work.
- Token expiry — per-page-load + retry-once hook; documented.
- Stale committed bundle — reproducible-build check in verification; CI guard (`npm ci && build && git diff --exit-code public`) listed as follow-up.
