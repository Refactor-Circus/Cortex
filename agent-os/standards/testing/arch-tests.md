# Arch Tests

Global code constraints are executable — they live in `tests/ArchTest.php`, not in review checklists:

```php
arch()->preset()->php();
arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('RefactorCircus\Cortex')
    ->toUseStrictTypes();
```

- Every PHP file: `declare(strict_types=1)` — enforced
- Banned everywhere: `dd`, `ddd`, `env` (use `config()`), `exit`
- New global constraint? Add an arch expectation here — that's the enforcement point
