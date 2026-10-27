---
description: "Cortex's Testbench TestCase pins the environment: array cache and cortex.cache.store, testing database with foreign key constraints, provider order Ai, Mcp, Atrium, Cortex, package migrations; per-test config via config()->set or a TestCase subclass."
tags: [cortex, testing, testbench, environment]
kind: convention
---
# Testbench Environment

Complements [[test-layers]]: what `RefactorCircus\Cortex\Tests\TestCase` (Orchestra Testbench) pins, and how tests change it.

## Pinned environment (don't undo these)

- `cache.default => array` and `cortex.cache.store => array`: the `PublicationCache` availability probe would otherwise find a running local Redis and leak published state across tests.
- `database.default => testing` with `foreign_key_constraints => true`: FK violations must fail in tests.
- Providers in this order: `AiServiceProvider`, `McpServiceProvider`, `AtriumServiceProvider`, then `CortexServiceProvider`.
- Package migrations load from `database/migrations`.

## Per-test config

- Call `config()->set(...)` at the start of the test, or use a dedicated `TestCase` subclass (`PennantPlusTestCase`, `RenamedTablesTestCase` under `tests/Modes/`). Never mutate the shared `TestCase` for one feature.
