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

it('names neither Atrium nor PennantPlus in any package section', function (string $package): void {
    $composer = composerManifest();

    foreach (['require', 'require-dev', 'suggest', 'conflict', 'replace', 'provide'] as $section) {
        expect($composer[$section] ?? [])->not->toHaveKey($package);
    }
})->with([
    'Atrium' => ['refactor-circus/atrium'],
    'PennantPlus' => ['refactor-circus/pennantplus'],
]);

it('adds no Atrium or PennantPlus repository', function (): void {
    $urls = array_map(
        fn (array $repository): string => strtolower((string) ($repository['url'] ?? '')),
        composerManifest()['repositories'] ?? [],
    );

    expect(array_filter($urls, fn (string $url): bool => str_contains($url, 'atrium') || str_contains($url, 'pennantplus')))->toBe([]);
});

it('declares only its Atrium front-end module, as JS and CSS in dist/atrium', function (): void {
    expect(composerManifest()['extra']['atrium'] ?? null)->toBe([
        'key' => 'cortex',
        'scripts' => ['dist/atrium/atrium.js'],
        'styles' => ['dist/atrium/atrium.css'],
    ]);
});

it('names neither Atrium nor PennantPlus anywhere else in the manifest', function (): void {
    $manifest = strtolower(manifestWithoutAtriumModule());

    expect($manifest)->not->toContain('atrium')
        ->and($manifest)->not->toContain('pennantplus');
});

it('runs no without-Atrium suite', function (): void {
    $scripts = composerManifest()['scripts'];

    expect($scripts)->not->toHaveKey('test:without-atrium')
        ->and((array) $scripts['test'])->not->toContain('@test:without-atrium')
        ->and((array) $scripts['test'])->toContain('@test:unit');
});

it('has no without-Atrium CI job', function (): void {
    $workflow = (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/tests.yml');

    expect($workflow)->not->toContain('without-atrium');
});

it('depends on no Roster', function (): void {
    $composer = composerManifest();

    foreach (['require', 'require-dev', 'suggest'] as $section) {
        expect($composer[$section] ?? [])->not->toHaveKey('refactor-circus/roster');
    }
});
