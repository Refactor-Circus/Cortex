<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;

/*
 * The committed OpenAPI document and TypeScript client (AC 13 to 16): both
 * must match the routes Cortex registers, so Atrium v2 and other API clients
 * build on what the package serves. Regenerate them with
 * `composer client:generate`.
 */

function openApiPath(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

/**
 * @return array<string, mixed>
 */
function openApiJson(string $path): array
{
    /** @var array<string, mixed> */
    return json_decode((string) file_get_contents(openApiPath($path)), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * Every operation of the committed document, as "METHOD /path".
 *
 * @return list<string>
 */
function openApiOperations(): array
{
    /** @var array<string, array<string, mixed>> $paths */
    $paths = openApiJson('openapi.json')['paths'];
    $operations = [];

    foreach ($paths as $path => $item) {
        foreach (array_keys($item) as $method) {
            if (in_array($method, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'], true)) {
                $operations[] = strtoupper($method).' '.$path;
            }
        }
    }

    sort($operations);

    return $operations;
}

/**
 * Every registered `cortex.*` route, as "METHOD /path" relative to the
 * `cortex.routes.prefix` the document's server URL carries.
 *
 * @return list<string>
 */
function cortexRouteOperations(): array
{
    Route::getRoutes()->refreshNameLookups();
    $prefix = trim((string) config('cortex.routes.prefix'), '/');
    $operations = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (! $route instanceof RoutingRoute || ! str_starts_with((string) $route->getName(), 'cortex.')) {
            continue;
        }

        // OpenAPI has no optional path parameters: `{name?}` reads `{name}`.
        $uri = str_replace('?}', '}', trim($route->uri(), '/'));
        $path = '/'.ltrim($prefix === '' ? $uri : (string) preg_replace('#^'.preg_quote($prefix, '#').'(/|$)#', '', $uri), '/');

        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            $operations[] = "{$method} {$path}";
        }
    }

    sort($operations);

    return $operations;
}

it('matches a fresh export of the workbench', function (): void {
    $path = (string) tempnam(sys_get_temp_dir(), 'cortex-openapi-');

    try {
        $result = Process::path(openApiPath())
            ->timeout(120)
            ->run([PHP_BINARY, 'vendor/bin/testbench', 'scramble:export', "--path={$path}"]);

        expect($result->successful())->toBeTrue($result->errorOutput().$result->output())
            ->and((string) file_get_contents($path))
            ->toBe((string) file_get_contents(openApiPath('openapi.json')), 'openapi.json is stale. Run composer client:generate.');
    } finally {
        @unlink($path);
    }
});

it('documents version 2.0.0', function (): void {
    expect(openApiJson('openapi.json')['info']['version'])->toBe('2.0.0');
});

it('documents every cortex route and no other', function (): void {
    $routes = cortexRouteOperations();

    expect($routes)->toContain('GET /search', 'GET /runnable-agents', 'GET /settings-summary', 'GET /redirect-domains')
        ->and(openApiOperations())->toBe($routes);
});

it('generated the client schema from the committed document', function (): void {
    $schema = (string) file_get_contents(openApiPath('client/src/schema.d.ts'));
    $firstLine = strtok($schema, "\n");
    $hash = hash_file('sha256', openApiPath('openapi.json'));

    expect($firstLine)->toBe("// openapi.json sha256: {$hash}; generated, do not edit. Regenerate with: composer client:generate", 'client/src/schema.d.ts is stale. Run composer client:generate.')
        ->and($schema)->toContain('export interface paths');
});

it('describes the client package', function (): void {
    $package = openApiJson('client/package.json');

    expect($package['name'])->toBe('@refactor-circus/cortex-client')
        ->and($package['version'])->toBe('2.0.0')
        ->and($package['types'])->toBe('./src/index.ts')
        ->and($package['exports'])->toBe(['.' => ['types' => './src/index.ts', 'default' => './src/index.ts']])
        ->and($package['dependencies'])->toHaveKey('openapi-fetch')
        ->and($package['devDependencies'])->toHaveKeys(['openapi-typescript', 'typescript'])
        ->and($package['scripts'])->toHaveKeys(['generate', 'typecheck'])
        ->and(file_exists(openApiPath('client/package-lock.json')))->toBeTrue();
});

it('exports createCortexClient and the schema types', function (): void {
    $index = (string) file_get_contents(openApiPath('client/src/index.ts'));

    expect($index)->toContain('export function createCortexClient(')
        ->and($index)->toContain('Client<paths>')
        ->and($index)->toMatch('/export type \{[^}]*\bpaths\b[^}]*\} from [\'"]\.\/schema[\'"]/')
        ->and($index)->toMatch('/export type \{[^}]*\bcomponents\b[^}]*\} from [\'"]\.\/schema[\'"]/')
        ->and($index)->toMatch('/export type \{[^}]*\boperations\b[^}]*\} from [\'"]\.\/schema[\'"]/');
});

it('needs Scramble for the workbench only', function (): void {
    /** @var array<string, mixed> $composer */
    $composer = json_decode((string) file_get_contents(openApiPath('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require-dev'])->toHaveKey('dedoc/scramble')
        ->and($composer['require'])->not->toHaveKey('dedoc/scramble')
        ->and($composer['suggest'] ?? [])->not->toHaveKey('dedoc/scramble')
        ->and((string) file_get_contents(openApiPath('testbench.yaml')))->toContain('Dedoc\Scramble\ScrambleServiceProvider')
        ->and($composer['scripts']['openapi:export'])->toBe(['@php vendor/bin/testbench scramble:export --path=openapi.json'])
        ->and($composer['scripts']['client:generate'])->toBe([
            '@openapi:export',
            'npm --prefix client ci',
            'npm --prefix client run generate',
            'npm --prefix client run typecheck',
        ]);
});

it('names the OpenAPI document and client in the routes banner', function (): void {
    $config = (string) file_get_contents(openApiPath('config/cortex.php'));
    $banner = substr($config, 0, (int) strpos($config, "'routes' =>"));

    expect($banner)->toContain('openapi.json')
        ->and($banner)->toContain('client/')
        ->and($banner)->toContain('composer client:generate');
});
