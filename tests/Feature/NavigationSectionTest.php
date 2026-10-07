<?php

declare(strict_types=1);

use JayI\Atrium\Support\Icons;
use JayI\Cortex\Atrium\CortexPlugin;

it('gives its sidebar section its own icon', function (): void {
    [$group] = app(CortexPlugin::class)->navigationGroups();

    expect($group->icon)->toBe(Icons::svg('cpu-chip'))
        ->and($group->sort)->toBe(50);
});

it('collects its pages in that section', function (): void {
    $plugin = app(CortexPlugin::class);
    [$group] = $plugin->navigationGroups();

    $labels = array_values(array_unique(array_map(fn ($item): ?string => $item->group, $plugin->navigation())));

    expect($labels)->toBe([$group->name]);
});
