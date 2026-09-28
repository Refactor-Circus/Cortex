<?php

declare(strict_types=1);

use JayI\Cortex\Cortex;

it('resolves the singleton', function () {
    expect(app(Cortex::class))->toBeInstanceOf(Cortex::class);
});

it('returns the same instance from the container', function () {
    expect(app(Cortex::class))->toBe(app(Cortex::class));
});

it('merges the package config', function () {
    expect(config('cortex.routes.prefix'))->toBe('cortex')
        ->and(config('cortex.routes.middleware'))->toBe(['api'])
        ->and(config('cortex.ui.enabled'))->toBeTrue()
        ->and(config('cortex.mcp.web.enabled'))->toBeFalse()
        ->and(config('cortex.mcp.local.enabled'))->toBeFalse()
        ->and(config('cortex.tools'))->toBe([])
        ->and(config('cortex.agents'))->toBe([]);
});

it('loads the package translations', function () {
    expect(trans('cortex::cortex.virtual_agents'))->toBe('Virtual agents');
});

it('loads the package views', function () {
    expect(view()->exists('cortex::ui.concrete-agents.index'))->toBeTrue()
        ->and(view()->exists('cortex::ui.virtual-agents.form'))->toBeTrue();
});
