<?php

declare(strict_types=1);

use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;

/*
 * AC 3, 6 and 22 (D-038, D-040, D-041): the Keen-conditional tests skip when
 * refactor-circus/keen is missing, so CI must always have it, and CI must
 * regenerate the autoloader before the tests so the shim file is listed.
 */

/**
 * The CI environment variable, or false when it is not set.
 */
function keenCiVariable(): string|false
{
    $value = getenv('CI');

    if ($value !== false) {
        return $value;
    }

    $value = $_SERVER['CI'] ?? $_ENV['CI'] ?? null;

    return is_string($value) ? $value : false;
}

/**
 * Whether the CI variable counts as set: any value except empty, `0` and `false`.
 */
function keenRunsInCi(string|false $ci): bool
{
    return $ci !== false && ! in_array(strtolower(trim($ci)), ['', '0', 'false'], true);
}

/**
 * Why the run must fail, or null when it may go on: CI is set and the Keen
 * audit model is missing, so the Keen tests would be skipped silently.
 */
function keenInCiFailure(string|false $ci, bool $keenInstalled): ?string
{
    if (! keenRunsInCi($ci) || $keenInstalled) {
        return null;
    }

    return 'The CI environment variable is set but '.AuditEntryModel::class.' does not exist, so the Keen-conditional '
        .'tests would be skipped. Keep refactor-circus/keen in require-dev and install it in CI.';
}

it('has Keen installed when the CI variable is set', function (): void {
    $ci = keenCiVariable();
    $installed = class_exists(AuditEntryModel::class);

    if (! keenRunsInCi($ci) && ! $installed) {
        $this->markTestSkipped('refactor-circus/keen is not installed; the guard only fails when CI is set.');
    }

    $failure = keenInCiFailure($ci, $installed);

    expect($failure)->toBeNull((string) $failure);
});

it('fails only when CI is set and Keen is missing', function (string|false $ci, bool $installed, bool $fails): void {
    $failure = keenInCiFailure($ci, $installed);

    if ($fails) {
        expect($failure)->toContain(AuditEntryModel::class)->toContain('refactor-circus/keen');
    } else {
        expect($failure)->toBeNull();
    }
})->with([
    'CI=true, Keen missing' => ['true', false, true],
    'CI=1, Keen missing' => ['1', false, true],
    'CI=true, Keen installed' => ['true', true, false],
    'CI unset, Keen missing' => [false, false, false],
    'CI empty, Keen missing' => ['', false, false],
    'CI=false, Keen missing' => ['false', false, false],
    'CI=0, Keen missing' => ['0', false, false],
]);

it('keeps refactor-circus/keen in require-dev with a lock entry', function (): void {
    $root = dirname(__DIR__, 3);

    /** @var array{require: array<string, string>, require-dev: array<string, string>} $composer */
    $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    /** @var array{packages-dev: list<array{name: string}>} $lock */
    $lock = json_decode((string) file_get_contents($root.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require-dev'])->toHaveKey('refactor-circus/keen')
        ->and($composer['require'])->not->toHaveKey('refactor-circus/keen')
        ->and(array_column($lock['packages-dev'], 'name'))->toContain('refactor-circus/keen');
});

it('regenerates the autoloader first in composer test', function (): void {
    $root = dirname(__DIR__, 3);

    /** @var array{scripts: array{test: list<string>}} $composer */
    $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['scripts']['test'][0] ?? null)->toBe('@composer dump-autoload');
});

it('regenerates the autoloader in CI after the install and before the tests', function (): void {
    $workflow = str_replace("\r\n", "\n", (string) file_get_contents(dirname(__DIR__, 3).'/.github/workflows/tests.yml'));

    $position = function (string $pattern) use ($workflow): int {
        expect(preg_match($pattern, $workflow, $match, PREG_OFFSET_CAPTURE))->toBe(1, "The CI workflow has no step matching {$pattern}.");

        return $match[0][1];
    };

    $install = $position('/^\s+composer update\b/m');
    $dump = $position('/^\s+run: composer dump-autoload\s*$/m');

    expect($dump)->toBeGreaterThan($install)
        ->toBeLessThan($position('/^\s+run: composer test:unit\s*$/m'))
        ->toBeLessThan($position('/^\s+run: vendor\/bin\/pest\s*$/m'));
});
