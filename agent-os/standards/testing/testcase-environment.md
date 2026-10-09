# TestCase Environment

All tests extend `RefactorCircus\Cortex\Tests\TestCase` (Orchestra Testbench), bound in `Pest.php` via `uses(TestCase::class)->in(__DIR__)`.

Pinned environment — don't undo these:

- `cortex.cache.store => array`: the PublicationCache availability probe would otherwise find a running local Redis and leak published state across tests
- `database.default => testing` with `foreign_key_constraints => true` — FK violations must fail in tests
- Providers: `AiServiceProvider`, `McpServiceProvider`, `AtriumServiceProvider`, then `CortexServiceProvider`
- Package migrations loaded from `database/migrations`

Per-test config: call `config()->set(...)` at the start of the test (or a dedicated TestCase subclass) — never mutate the shared `TestCase` for one feature.
