# Cortex

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `jayi/cortex`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Layout

The package follows the mono domain-module layout, described in `agent-os/standards/backend/domain-modules.md`. Code lives in `src/Domains/{VirtualAgent,ConcreteAgent,Tool,McpServer}` (namespace `JayI\Cortex\Domains\{Domain}`), each with its own `{Domain}ServiceProvider` registered by `Domains\DomainServiceProvider`. Models are named `{Entity}Model`. The Atrium screens span every domain and live in `src/Atrium`; package-wide pieces (`Cortex`, the facade, contracts, base requests, `Mcp\CortexServer`, `Support\`) stay at the top level. `config/cortex.php` stays one file.

## Layout

The package follows the mono domain-module layout, described in `agent-os/standards/backend/domain-modules.md`. Code lives in `src/Domains/{VirtualAgent,ConcreteAgent,Tool,McpServer}` (namespace `JayI\Cortex\Domains\{Domain}`), each with its own `{Domain}ServiceProvider` registered by `Domains\DomainServiceProvider`. Models are named `{Entity}Model`. The Atrium screens span every domain and live in `src/Atrium`; package-wide pieces (`Cortex`, the facade, contracts, base requests, `Mcp\CortexServer`, `Support\`) stay at the top level. `config/cortex.php` stays one file.

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
