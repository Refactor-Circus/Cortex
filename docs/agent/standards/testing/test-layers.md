---
description: 'Cortex override of testing/test-layers: behavior tested once at the action, surfaces test only their wiring, route names not URLs; TestCase is bound to Unit as well as Feature because some unit tests need Testbench, but only a unit test of one class that needs the container or config may boot it.'
tags:
  - testing
  - architecture
  - layers
  - cortex
kind: architectural
overrides: refactor-circus/standards
reviewed_hash: 1cf688063fa0
---
# Test Layers

Behavior is tested once, at the action; each surface tests only its own wiring.

## Layers

| Layer | Location | Invokes via | Covers |
|---|---|---|---|
| Unit | `tests/Unit/` | the class directly | One class in isolation: value objects, encoders, calculations, and single services that need the container or config |
| Actions | `tests/Feature/*ActionsTest.php` | `app({Action}::class)->execute(...)` | Business rules, edge cases, guards, events |
| HTTP | `tests/Feature/Http/` | `$this->postJson(route('cortex.…'))` | Status codes, validation mapping, authorization, JSON paths |
| Console | `tests/Feature/Console/` | `$this->artisan('cortex:…')` | Arguments, prompts, output, exit codes |
| Other surfaces | `tests/Feature/{Surface}/` | the surface's own entry point | Its envelope, errors, parity with HTTP |
| Architecture | `tests/ArchTest.php` | `arch()` | Structural rules ([[arch-tests]]) |

## Rules

- Do not repeat a business case per surface. A "cannot delete while in use" rule is one action test, not one per surface; the HTTP test only checks that the refusal maps to the right status.
- A surface test proves the surface reaches the action and shapes its result: routing, validation, authorization ([[authorization]]), status, and the resource payload ([[resources]]).
- HTTP tests refer to routes by name, never by literal URL ([[routes]]).
- Set up data with model factories. Shared fixtures (fake implementations, test policies, extra servers) live in `tests/Fixtures/`.

## Unit vs feature

Cortex differs from the global standard here: `TestCase` (Testbench) is bound to `Unit` as well as `Feature`, because some unit tests need Testbench.

- A unit test still tests one class directly. It may boot Testbench only when that class needs the container or config to exist (resolved through `app()`, reads `config()`, or depends on a bound Laravel AI or MCP service). It uses no database, routes, HTTP calls or other surfaces.
- A unit test of pure logic does not use what the boot provides: write it as plain PHP, and don't add container or config calls to it.
- `tests/Feature/` boots the package for anything that needs the database, routes, macros or a surface, and for every action and surface test.
- Put a test in `Feature` when it uses more than the container and config of one class.

```php
// tests/Pest.php
uses(TestCase::class)->in('ArchTest.php', 'Feature', 'Unit'); // Unit tests may need Testbench
uses(PennantPlusTestCase::class)->in('Modes/PennantPlus');
uses(RenamedTablesTestCase::class)->in('Modes/RenamedTables');
```

The pinned Testbench environment is in [[testbench-environment]]; MCP surface tests follow [[mcp-http-parity]].

## Reference implementation (Refactor Circus)

`RefactorCircus\Keystone\Testing\Parity::gaps()` lists actions a surface cannot reach, so a parity test can assert every action is offered on each surface the package has.
