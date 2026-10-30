<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Cortex\CortexServiceProvider;

/**
 * Cortex's redirect-domain UI goes onto other packages' screens only through
 * `cortex.atrium.redirect_domains.screens`. Each test empties the registry,
 * sets the map and runs the provider's registration again, as `boot()` does.
 */
function bootScreenRegistrations(): void
{
    app(ScreenExtensions::class)->flush();

    $provider = app()->getProvider(CortexServiceProvider::class);

    (fn () => $this->registerAtriumScreens())->call($provider);
}

/**
 * Every key of the tabs and sections registered for the target.
 *
 * @return array{tabs: list<string>, sections: array<string, list<string>>}
 */
function screenRegistrationsOn(string $target): array
{
    $registry = app(ScreenExtensions::class);
    $sections = [];

    foreach (['header.actions', 'before', 'after', 'aside', 'row.actions'] as $slot) {
        $keys = array_map(fn (SectionDefinition $section): string => $section->key, $registry->sections($target, $slot));

        if ($keys !== []) {
            $sections[$slot] = $keys;
        }
    }

    return [
        'tabs' => array_map(fn (TabDefinition $tab): string => $tab->key, $registry->tabs($target)),
        'sections' => $sections,
    ];
}

it('defaults to the organization tab and the user aside', function (): void {
    expect(config('cortex.atrium.redirect_domains.screens'))->toBe([
        'roster.organization' => 'tab',
        'roster.user' => 'aside',
    ]);
});

it('registers the default map from boot', function (): void {
    $tabs = app(ScreenExtensions::class)->tabs('roster.organization');
    $sections = app(ScreenExtensions::class)->sections('roster.user', 'aside');

    expect($tabs)->toHaveCount(1)
        ->and($tabs[0]->key)->toBe('cortex.mcp')
        ->and($tabs[0]->label)->toBe('MCP')
        ->and($tabs[0]->icon)->toBe('globe-alt')
        ->and($tabs[0]->view)->toBe('cortex::ui.screens.redirect-domains')
        ->and($tabs[0]->permission)->toBeNull()
        ->and($tabs[0]->public)->toBeFalse()
        ->and($sections)->toHaveCount(1)
        ->and($sections[0]->key)->toBe('cortex.redirect-domains')
        ->and($sections[0]->label)->toBe('Redirect domains')
        ->and($sections[0]->view)->toBe('cortex::ui.screens.redirect-domains')
        ->and($sections[0]->permission)->toBeNull()
        ->and($sections[0]->public)->toBeFalse()
        ->and(screenRegistrationsOn('roster.organization'))->toBe(['tabs' => ['cortex.mcp'], 'sections' => []])
        ->and(screenRegistrationsOn('roster.user'))->toBe(['tabs' => [], 'sections' => ['aside' => ['cortex.redirect-domains']]]);
});

it('uses the default map when a published config has no redirect_domains key', function (): void {
    // An `atrium` section published before the key existed.
    config()->set('cortex.atrium', ['features' => []]);

    bootScreenRegistrations();

    expect(screenRegistrationsOn('roster.organization')['tabs'])->toBe(['cortex.mcp'])
        ->and(screenRegistrationsOn('roster.user')['sections'])->toBe(['aside' => ['cortex.redirect-domains']]);
});

it('registers nothing for an empty list', function (): void {
    config()->set('cortex.atrium.redirect_domains.screens', []);

    bootScreenRegistrations();

    expect(screenRegistrationsOn('roster.organization'))->toBe(['tabs' => [], 'sections' => []])
        ->and(screenRegistrationsOn('roster.user'))->toBe(['tabs' => [], 'sections' => []]);
});

it('registers only the entries of a custom list', function (): void {
    config()->set('cortex.atrium.redirect_domains.screens', ['acme.account' => 'before']);

    bootScreenRegistrations();

    expect(screenRegistrationsOn('acme.account'))->toBe(['tabs' => [], 'sections' => ['before' => ['cortex.redirect-domains']]])
        ->and(screenRegistrationsOn('roster.organization'))->toBe(['tabs' => [], 'sections' => []])
        ->and(screenRegistrationsOn('roster.user'))->toBe(['tabs' => [], 'sections' => []]);
});

it('registers nothing while the dashboard UI is switched off', function (): void {
    config()->set('cortex.ui.enabled', false);

    bootScreenRegistrations();

    expect(screenRegistrationsOn('roster.organization'))->toBe(['tabs' => [], 'sections' => []])
        ->and(screenRegistrationsOn('roster.user'))->toBe(['tabs' => [], 'sections' => []]);
});

it('registers nothing unless the UI flag is literally true', function (): void {
    config()->set('cortex.ui.enabled', '1');

    bootScreenRegistrations();

    expect(screenRegistrationsOn('roster.organization'))->toBe(['tabs' => [], 'sections' => []]);
});

it('throws for a malformed entry in testing', function (mixed $screens): void {
    config()->set('cortex.atrium.redirect_domains.screens', $screens);

    expect(fn () => bootScreenRegistrations())->toThrow(InvalidArgumentException::class);
})->with([
    'a null value' => [['roster.organization' => null]],
    'an empty value' => [['roster.organization' => '']],
    'a bogus slot' => [['roster.organization' => 'bogus slot']],
    'a value that is not a string' => [['roster.organization' => ['tab']]],
    'a bogus target' => [['Not A Screen' => 'tab']],
    'a list without screen keys' => [['tab']],
    'a map that is not an array' => ['tab'],
]);

it('throws in local too', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    config()->set('cortex.atrium.redirect_domains.screens', ['roster.organization' => '']);

    expect(fn () => bootScreenRegistrations())->toThrow(InvalidArgumentException::class);
});

it('reports a malformed entry once in production, skips it and registers the rest', function (): void {
    Exceptions::fake();
    app()->detectEnvironment(fn (): string => 'production');
    config()->set('cortex.atrium.redirect_domains.screens', [
        'roster.organization' => 'bogus slot',
        'roster.user' => 'aside',
    ]);

    bootScreenRegistrations();

    Exceptions::assertReportedCount(1);
    Exceptions::assertReported(InvalidArgumentException::class);

    expect(screenRegistrationsOn('roster.organization'))->toBe(['tabs' => [], 'sections' => []])
        ->and(screenRegistrationsOn('roster.user')['sections'])->toBe(['aside' => ['cortex.redirect-domains']]);
});
