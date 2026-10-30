<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Cortex\CortexServiceProvider;

it('does not register mcp transports by default', function () {
    expect(Mcp::getWebServer('mcp/cortex'))->toBeNull()
        ->and(Mcp::getLocalServer('cortex'))->toBeNull();
});

it('registers the web transport when enabled', function () {
    config()->set('cortex.mcp.web.enabled', true);

    // Booting again registers Cortex's screen extensions again, which Atrium
    // refuses as duplicates in testing.
    app(ScreenExtensions::class)->flush();

    (new CortexServiceProvider(app()))->boot();

    expect(Mcp::getWebServer('mcp/cortex'))->not->toBeNull();
});

it('registers the local transport when enabled', function () {
    config()->set('cortex.mcp.local.enabled', true);

    // Booting again registers Cortex's screen extensions again, which Atrium
    // refuses as duplicates in testing.
    app(ScreenExtensions::class)->flush();

    (new CortexServiceProvider(app()))->boot();

    expect(Mcp::getLocalServer('cortex'))->not->toBeNull();
});
