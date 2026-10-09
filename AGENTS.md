# Cortex

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `jayi/cortex`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Layout

The package follows the mono domain-module layout, described in `agent-os/standards/backend/domain-modules.md`. Code lives in `src/Domains/{VirtualAgent,ConcreteAgent,Tool,McpServer,RedirectDomain}` (namespace `JayI\Cortex\Domains\{Domain}`), each with its own `{Domain}ServiceProvider` registered by `Domains\DomainServiceProvider`. Models are named `{Entity}Model`. The Atrium screens span every domain and live in `src/Atrium`; package-wide pieces (`Cortex`, the facade, the base requests, `Mcp\CortexServer`, `Support\`) stay at the top level. `config/cortex.php` stays one file.

## Foundation

Cortex stands on `jayi/foundation`, the shared runtime of the jayi suite; read its README before changing base classes.

- `CortexServiceProvider` extends `JayI\Foundation\Support\PackageServiceProvider`. `definition()` describes the package (`cortex`, `JayI\Cortex`, `CortexServer`); `register()` merges the config, calls `registerPackage()`, then registers the domain providers. `boot()` uses `registerPolicies()`, `registerAtriumPlugin()`, `registerMcpServer()` and `loadHistoryRoutes()`. It does not call `registerCortex()`: Cortex registers its own server with its own registry, and its management tools are not offered to agents.
- Domain providers extend `JayI\Foundation\Support\ServiceProvider`; routes load through `loadApiRoutesFrom()`, gated by `cortex.routes.enabled`.
- Events implement `JayI\Foundation\Contracts\{ActionStartingEvent,ActionFinishedEvent,ModelLifecycleEvent}`; models use `JayI\Foundation\Models\Concerns\DispatchesModelEvents`.
- `Http\Request` and `Mcp\Request` extend Foundation's request bases but keep Cortex's authorization: every check goes to the Gate, as the signed-in user or as a guest, and the bundled policies allow guests. Do not switch them to Foundation's `authorization` switch.
- `Mcp\CortexServer` extends `JayI\Foundation\Mcp\Server` and lists every tool in `TOOLS`, including `Mcp\Tools\ListCortexHistoryTool`. Cortex's own tools extend `JayI\Foundation\Mcp\Tool`; `Domains\Tool\Support\Tool` and `Domains\McpServer\Support\Server` are for application tools and servers.

## Frontend

Atrium owns every component and style; Cortex ships no `resources/css`, no `Atrium::css()` call and no component namespace. See `agent-os/standards/frontend/atrium-screens.md`.

- Views use only `x-atrium::*` components and the utilities Atrium safelists. No `<style>` blocks and no `style=` attributes. `tests/Feature/Ui/StylesTest.php` checks this with `JayI\Atrium\Testing\AtriumStyles`.
- Status and errors use `<x-atrium::flash :keys="['prompt', 'agent']" />`; tag filters are `x-atrium::chip`s; searches are `x-atrium::search-input`.
- History is `<x-atrium::audit-trail source="cortex" />`, with `:subject` on a record's screen. The content-versions partial stays, since versions are publishable content.
- `CortexPlugin::features()` uses Atrium's `featuresFromConfig()`. Cortex keeps its own `Atrium\ScreenAccess` and `AuthorizesScreens`: Atrium's copies follow Foundation's `authorization` switch and refuse guests, while Cortex asks the Gate as the signed-in user or as a guest, with extra arguments.

## Quick Commands

- Full validation: `composer test`
- Formatting check: `composer lint:check`
- Static analysis: `composer analyse`
- Pest tests: `composer test:unit`
- Workbench build: `composer build`
- Workbench server: `composer serve`

## Local Skills

- `package-scaffold`: use when adding package capabilities or wiring them through the service provider, including commands, migrations, routes, config, views, translations, assets, middleware, publish tags, workbench files, and console-only behavior.
- `package-testing`: use when adding or changing package tests with Pest 4 and Orchestra Testbench.
- `package-release`: use when preparing changelog, release notes, tags, or GitHub release workflow changes.
- `package-compatibility`: use when reviewing code, dependencies, or CI against the PHP and Laravel support matrix.
- `package-generate-skill`: use when updating the bundled Boost skill from the package implementation, README, and examples.
