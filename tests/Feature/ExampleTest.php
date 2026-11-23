<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Cortex;

it('resolves the singleton', function () {
    expect(app(Cortex::class))->toBeInstanceOf(Cortex::class);
});

it('returns the same instance from the container', function () {
    expect(app(Cortex::class))->toBe(app(Cortex::class));
});

it('merges the package config', function () {
    expect(config('cortex.routes.prefix'))->toBe('cortex')
        ->and(config('cortex.routes.middleware'))->toBe(['api'])
        ->and(config('cortex.mcp.web.enabled'))->toBeFalse()
        ->and(config('cortex.mcp.local.enabled'))->toBeFalse()
        ->and(config('cortex.tools'))->toBe([])
        ->and(config('cortex.agents'))->toBe([]);
});

it('loads the package translations', function () {
    expect(trans('cortex::cortex.redirect_domain_invalid'))->toBe('Enter a host or an http(s) URL, such as claude.ai or https://claude.ai.');
});
