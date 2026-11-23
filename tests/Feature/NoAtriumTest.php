<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/*
 * Cortex v2 ships its JSON API and MCP server only (AC 8 to 12): the Atrium
 * screens moved to Atrium v2, which builds them on the API.
 */

/**
 * The package files AC 9 covers: everything under the listed folders (minus
 * the workbench's git-ignored runtime files) and the listed root files.
 *
 * @return list<string>
 */
function shippedFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = [];

    foreach (['src', 'config', 'lang', 'workbench', '.github/workflows'] as $folder) {
        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$folder}", FilesystemIterator::SKIP_DOTS));

        foreach ($entries as $entry) {
            if (! $entry instanceof SplFileInfo || ! $entry->isFile()) {
                continue;
            }

            $path = str_replace('\\', '/', $entry->getPathname());

            // workbench/.gitignore: the runtime storage and local env files.
            if (str_contains($path, '/workbench/storage/') || preg_match('#/workbench/\.env[^/]*$#', $path) === 1) {
                continue;
            }

            $files[] = $path;
        }
    }

    foreach (['composer.json', 'testbench.yaml', 'phpunit.xml.dist'] as $file) {
        $files[] = "{$root}/{$file}";
    }

    return $files;
}

it('names neither Atrium nor PennantPlus in the package files', function (string $word): void {
    $files = shippedFiles();
    $naming = array_values(array_filter(
        $files,
        fn (string $path): bool => stripos((string) file_get_contents($path), $word) !== false,
    ));

    expect(count($files))->toBeGreaterThan(3)
        ->and($naming)->toBe([]);
})->with(['atrium', 'pennantplus']);

it('ships none of the removed Atrium files', function (string $path): void {
    expect(file_exists(dirname(__DIR__, 2).'/'.$path))->toBeFalse("{$path} still exists.");
})->with([
    'src/Atrium',
    'resources/views',
    'tests/Feature/Ui',
    'tests/WithoutAtrium',
    'tests/Modes/PennantPlus',
    'tests/PennantPlusTestCase.php',
    'tests/Fixtures/views/screen-probe.blade.php',
    'tests/Feature/HistoryNavigationTest.php',
    'tests/Feature/NavigationSectionTest.php',
    'tests/Feature/NoHistoryUiTest.php',
    'phpunit.without-atrium.xml',
    'workbench/app/Http/Controllers/TestHostScreenController.php',
    'workbench/resources/views/test-host.blade.php',
]);

it('registers no atrium route', function (): void {
    Route::getRoutes()->refreshNameLookups();
    $names = array_keys(Route::getRoutes()->getRoutesByName());

    expect($names)->toContain('cortex.search.index')
        ->and(array_filter($names, fn (string $name): bool => str_starts_with($name, 'atrium.')))->toBe([]);
});

it('has no ui or atrium config', function (): void {
    expect(config('cortex.ui'))->toBeNull()
        ->and(config('cortex.atrium'))->toBeNull();
});

it('registers no cortexCan Blade directive', function (): void {
    expect(Blade::getCustomDirectives())->not->toHaveKey('cortexCan')
        ->not->toHaveKey('endcortexCan');
});

it('publishes no views', function (): void {
    expect(ServiceProvider::publishableGroups())->toContain('cortex-config', 'cortex-lang')
        ->not->toContain('cortex-views');
});

it('keeps only translation keys the source uses', function (): void {
    /** @var array<string, string> $lines */
    $lines = require dirname(__DIR__, 2).'/lang/en/cortex.php';
    $source = implode("\n", array_map(
        fn (string $path): string => (string) file_get_contents($path),
        array_filter(shippedFiles(), fn (string $path): bool => str_contains($path, '/src/')),
    ));

    $unused = array_values(array_filter(
        array_keys($lines),
        fn (string $key): bool => ! str_contains($source, "cortex::cortex.{$key}"),
    ));

    expect($lines)->not->toBeEmpty()
        ->and($unused)->toBe([]);
});
