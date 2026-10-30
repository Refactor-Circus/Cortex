<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Cortex\Atrium\CortexPlugin;

it('links no history page from its sidebar group', function (): void {
    // History is Keen's: it attaches to every screen by wildcard.
    $routes = array_map(fn (NavItem $item): ?string => $item->route, app(CortexPlugin::class)->navigation());

    expect($routes)->not->toContain('atrium.history.show');
});
