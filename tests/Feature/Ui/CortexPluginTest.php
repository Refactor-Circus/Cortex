<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JayI\Atrium\Domains\Navigation\Data\NavItem;
use JayI\Atrium\Domains\Plugins\Services\PluginRegistry;
use JayI\Cortex\Atrium\CortexPlugin;
use JayI\Cortex\Tests\Fixtures\OrphanedSupportFeature;

it('registers itself with atrium', function (): void {
    expect(app(PluginRegistry::class)->has('cortex'))->toBeTrue();
});

it('contributes navigation for every section', function (): void {
    $labels = array_map(
        fn (NavItem $item): string => $item->label,
        app(CortexPlugin::class)->navigation(),
    );

    expect($labels)->toBe(['Virtual agents', 'Concrete agents', 'Run agent', 'Tools', 'Servers', 'Audit log']);
});

it('registers its routes inside the atrium group', function (): void {
    expect(Route::has('atrium.cortex.virtual-agents.index'))->toBeTrue()
        ->and(Route::has('atrium.cortex.concrete-agents.index'))->toBeTrue()
        ->and(Route::has('atrium.cortex.run'))->toBeTrue()
        ->and(route('atrium.cortex.virtual-agents.index'))->toContain('/atrium/cortex/virtual-agents');
});

it('offers a settings panel', function (): void {
    $panel = app(CortexPlugin::class)->settings();

    expect($panel)->not->toBeNull()
        ->and($panel->key)->toBe('cortex');
});

it('offers no widgets', function (): void {
    // Cortex contributes pages, not dashboard widgets.
    expect(app(CortexPlugin::class)->widgets())->toBe([]);
});

it('gives every navigation item an icon', function (): void {
    foreach (app(CortexPlugin::class)->navigation() as $item) {
        expect($item->icon)->toContain('<svg');
    }
});

it('skips a feature class whose parent is not installed rather than failing', function (): void {
    // As CortexSupportFeature is without jayi/pennantplus.
    config()->set('cortex.atrium.features', [OrphanedSupportFeature::class, 'App\\Features\\Missing', 'plain-feature']);

    expect(app(CortexPlugin::class)->features())->toBe(['plain-feature']);
});
