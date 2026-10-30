<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function composerManifest(): array
{
    /** @var array<string, mixed> */
    return json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
}

it('requires Keystone and no other suite package', function (): void {
    $suite = array_values(array_filter(
        array_keys(composerManifest()['require']),
        fn (string $package): bool => str_starts_with($package, 'refactor-circus/'),
    ));

    expect($suite)->toBe(['refactor-circus/keystone']);
});

it('suggests Atrium and needs it only for its own suite', function (): void {
    $composer = composerManifest();

    expect($composer['suggest'])->toHaveKey('refactor-circus/atrium')
        ->and(trim((string) $composer['suggest']['refactor-circus/atrium']))->not->toBe('')
        ->and($composer['require-dev'])->toHaveKey('refactor-circus/atrium')
        ->and($composer['require'])->not->toHaveKey('refactor-circus/atrium');
});

it('refuses an Atrium older than the screen-extensions registry', function (): void {
    $constraint = composerManifest()['conflict']['refactor-circus/atrium'] ?? '';

    expect($constraint)->toStartWith('<')
        ->and($constraint)->toBe('<0.1.0');
});

it('depends on no Roster', function (): void {
    $composer = composerManifest();

    foreach (['require', 'require-dev', 'suggest'] as $section) {
        expect($composer[$section] ?? [])->not->toHaveKey('refactor-circus/roster');
    }
});

it('runs the no-Atrium suite after the unit suite in composer test', function (): void {
    $scripts = composerManifest()['scripts'];
    $test = $scripts['test'];

    expect($scripts)->toHaveKey('test:without-atrium')
        ->and(implode(' ', (array) $scripts['test:without-atrium']))->toContain('phpunit.without-atrium.xml')
        ->and($test)->toContain('@test:unit', '@test:without-atrium')
        ->and(array_search('@test:without-atrium', $test, true))->toBeGreaterThan(array_search('@test:unit', $test, true));
});

it('proves the package runs with Atrium uninstalled in CI', function (): void {
    $workflow = (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/tests.yml');

    expect($workflow)->toContain('without-atrium:')
        ->and($workflow)->toContain('composer remove --dev refactor-circus/atrium --no-update')
        ->and($workflow)->toContain('composer test:without-atrium');
});
