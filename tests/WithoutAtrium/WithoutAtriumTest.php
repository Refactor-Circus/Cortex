<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Cortex\CortexServiceProvider;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\ListRedirectDomainsTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

// The suite runs in file order (phpunit.without-atrium.xml), so the removal
// of Atrium's autoload prefix is proven before anything else runs.
it('removed Atrium from every Composer autoloader', function (): void {
    expect(ClassLoader::getRegisteredLoaders())->not->toBeEmpty();

    foreach (ClassLoader::getRegisteredLoaders() as $loader) {
        expect($loader->getPrefixesPsr4())->not->toHaveKey('RefactorCircus\\Atrium\\')
            ->and($loader->findFile('RefactorCircus\\Atrium\\Atrium'))->toBeFalse();
    }
});

it('cannot load an Atrium class', function (): void {
    expect(class_exists('RefactorCircus\\Atrium\\Atrium'))->toBeFalse()
        ->and(class_exists('RefactorCircus\\Atrium\\AtriumServiceProvider'))->toBeFalse();
});

it('boots Cortex with the default redirect-domain screen map and registers nothing', function (): void {
    expect(app()->getProvider(CortexServiceProvider::class))->not->toBeNull()
        ->and(config('cortex.atrium.redirect_domains.screens'))->toBe([
            'roster.organization' => 'tab',
            'roster.user' => 'aside',
        ]);
});

it('serves no Atrium route', function (): void {
    $atrium = array_values(array_filter(
        array_keys(Route::getRoutes()->getRoutesByName()),
        fn (string $name): bool => str_starts_with($name, 'atrium.'),
    ));

    expect($atrium)->toBe([]);
});

it('serves the redirect-domain JSON API', function (): void {
    $this->getJson(route('cortex.redirect-domains.index'))->assertOk();

    $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'https://claude.ai/callback'])
        ->assertCreated()
        ->assertJsonPath('data.domain', 'https://claude.ai');

    expect(RedirectDomainModel::query()->where('domain', 'https://claude.ai')->exists())->toBeTrue();
});

it('answers MCP', function (): void {
    mcpTool(ListRedirectDomainsTool::class)->assertOk();
});

it('creates a virtual agent over the JSON API', function (): void {
    $this->postJson(route('cortex.virtual-agents.store'), [
        'name' => 'Coordinator',
        'slug' => 'coordinator',
        'instructions' => 'Coordinate.',
        'provider' => 'anthropic',
        'model' => 'claude-sonnet-5',
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'coordinator');

    expect(VirtualAgentModel::query()->where('slug', 'coordinator')->exists())->toBeTrue();
});
