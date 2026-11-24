<?php

declare(strict_types=1);

/*
 * Cortex ships its Atrium front-end module: the sources in atrium/ build to
 * dist/atrium (committed, since hosts never run Node), and composer.json's
 * `extra.atrium` points Atrium's page at the built script and stylesheet.
 * The build writes build.json with the SHA-256 of the files it was built
 * from; this recomputes it the way the SDK's preset does (`hashInputs()` in
 * @refactor-circus/atrium/vite), so a source changed without a rebuild fails
 * here with no Node needed. The ui workflow also rebuilds and diffs.
 * `extra.atrium.integration` points at the built integration manifest
 * (atrium/src/integration.json, validated and copied by the preset).
 */

function atriumModulePath(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

/**
 * The SHA-256 of `inputs` under `root`, as the preset's `hashInputs()` takes it.
 *
 * @param  list<string>  $inputs
 */
function atriumSourcesHash(string $root, array $inputs): string
{
    $files = [];

    $walk = function (string $path) use (&$walk, &$files, $root): void {
        $absolute = $root.'/'.$path;

        if (! file_exists($absolute)) {
            return;
        }

        if (is_dir($absolute)) {
            foreach (scandir($absolute) ?: [] as $entry) {
                if (! in_array($entry, ['node_modules', 'dist', '.DS_Store', '.git'], true) && ! str_starts_with($entry, '.')) {
                    $walk($path.'/'.$entry);
                }
            }

            return;
        }

        $files[] = $path;
    };

    foreach ($inputs as $input) {
        $walk(rtrim($input, '/'));
    }

    $files = array_values(array_unique($files));
    sort($files, SORT_STRING);

    $lines = array_map(
        fn (string $path): string => $path."\0".hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($root.'/'.$path)))."\n",
        $files,
    );

    return hash('sha256', implode('', $lines));
}

/**
 * @return array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string}
 */
function atriumBuildInfo(): array
{
    /** @var array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string} */
    return json_decode((string) file_get_contents(atriumModulePath('dist/atrium/build.json')), true, flags: JSON_THROW_ON_ERROR);
}

it('declares built files that exist', function (string $section): void {
    /** @var array{extra: array{atrium: array<string, list<string>>}} $manifest */
    $manifest = json_decode((string) file_get_contents(atriumModulePath('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $files = $manifest['extra']['atrium'][$section];

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        expect($file)->toStartWith('dist/atrium/')
            ->and(is_file(atriumModulePath($file)))->toBeTrue("{$file} is missing: run `pnpm -C atrium build` and commit dist/atrium.");
    }
})->with(['scripts', 'styles']);

it('declares its Atrium integration in a built manifest for the cortex key', function (): void {
    /** @var array{extra: array{atrium: array{integration: string}}} $composer */
    $composer = json_decode((string) file_get_contents(atriumModulePath('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $path = $composer['extra']['atrium']['integration'];

    expect($path)->toBe('dist/atrium/integration.json')
        ->and(is_file(atriumModulePath($path)))->toBeTrue("{$path} is missing: run `pnpm -C atrium build` and commit dist/atrium.");

    /** @var array{key: string, version: int} $manifest */
    $manifest = json_decode((string) file_get_contents(atriumModulePath($path)), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['key'])->toBe('cortex')
        ->and($manifest['version'])->toBe(1);
});

it('was built for the cortex key and shared API 1.0 from the atrium folder', function (): void {
    $info = atriumBuildInfo();

    expect($info['key'])->toBe('cortex')
        ->and($info['shared_api'])->toBe('1.0')
        ->and(realpath(atriumModulePath('dist/atrium').'/'.$info['root']))->toBe(realpath(atriumModulePath('atrium')));
});

it('was built from the sources as they are now', function (): void {
    $info = atriumBuildInfo();
    $root = (string) realpath(atriumModulePath('dist/atrium').'/'.$info['root']);

    expect(atriumSourcesHash($root, $info['inputs']))->toBe($info['hash'], 'dist/atrium is older than atrium/: run `pnpm -C atrium build` and commit the result.');
});

it('ships dist/atrium in the Composer archive and leaves the sources out', function (): void {
    $attributes = (string) file_get_contents(atriumModulePath('.gitattributes'));

    expect($attributes)->toMatch('#^/atrium\s+export-ignore$#m')
        ->not->toMatch('#^/dist\S*\s+export-ignore#m');
});
