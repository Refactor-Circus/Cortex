<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\SwitchableRedirectDomainPolicy;
use Workbench\App\Models\Organization;
use Workbench\App\Models\User;

/**
 * The `cortex.mcp` tab and the `cortex.redirect-domains` section show only
 * while stored domains are on, the record is a saved model, Cortex's plugin
 * is authorized for the request and the viewer passes `viewAny` with the
 * owner. Each case turns exactly one of those off.
 */
beforeEach(function (): void {
    SwitchableRedirectDomainPolicy::reset();
    Gate::policy(RedirectDomainModel::class, SwitchableRedirectDomainPolicy::class);

    $this->viewer = User::query()->create(['name' => 'Viewer', 'email' => 'viewer@example.com', 'password' => 'secret']);
    $this->actingAs($this->viewer);
});

afterEach(function (): void {
    SwitchableRedirectDomainPolicy::reset();
});

/**
 * @return array<string, array{0: string, 1: Closure(): (TabDefinition|SectionDefinition), 2: string}>
 */
function visibilityTargets(): array
{
    return [
        'the organization tab' => [
            'roster.organization',
            fn (): TabDefinition => Atrium::screens()->tabs('roster.organization')[0],
            'data-testid="tab-cortex.mcp"',
        ],
        'the user aside section' => [
            'roster.user',
            fn (): SectionDefinition => Atrium::screens()->sections('roster.user', 'aside')[0],
            'data-testid="section-cortex.redirect-domains"',
        ],
    ];
}

function visibilityRecord(string $key): Model
{
    return $key === 'roster.user'
        ? test()->viewer
        : Organization::query()->create(['name' => 'Acme Inc']);
}

/**
 * A new request each time: Atrium remembers a request's plugin answers.
 */
function visibilityAllows(TabDefinition|SectionDefinition $extension, string $key, ?Model $record): bool
{
    $request = Request::create('/test-host');
    $request->setUserResolver(fn (): mixed => auth()->user());

    return $extension->allows($request, $record, Screen::standard($key, Screen::DETAIL)->record($record), null);
}

/**
 * Each condition turned off on its own: it changes the app or the record and
 * answers the record to check (null for none).
 *
 * @return array<string, array{Closure(Model): ?Model}>
 */
function visibilityConditions(): array
{
    return [
        'stored domains switched off' => [function (Model $record): Model {
            config()->set('cortex.redirect_domains.enabled', false);

            return $record;
        }],
        'cortex plugin not authorized' => [function (Model $record): Model {
            // A feature Cortex requires is off, so PluginRegistry::authorized() lacks `cortex`.
            config()->set('cortex.atrium.features', ['plain-feature']);
            Atrium::resolveFeaturesUsing(fn (string $feature): bool => $feature !== 'plain-feature');

            return $record;
        }],
        'an unsaved record' => [fn (Model $record): Model => $record::class === User::class ? new User : new Organization],
        'no record' => [fn (Model $record): ?Model => null],
        'viewAny denied' => [function (Model $record): Model {
            SwitchableRedirectDomainPolicy::$denies = ['viewAny'];

            return $record;
        }],
    ];
}

it('is visible while every condition holds', function (string $key, Closure $extension, string $testId): void {
    $record = visibilityRecord($key);

    expect(visibilityAllows($extension(), $key, $record))->toBeTrue();

    $this->get(route('test-host', ['key' => $key, 'id' => $record->getKey()]))
        ->assertOk()
        ->assertSee($testId, false);
})->with(visibilityTargets());

it('is hidden when one condition fails', function (string $key, Closure $extension, string $testId, Closure $condition): void {
    $record = $condition(visibilityRecord($key));

    expect(visibilityAllows($extension(), $key, $record))->toBeFalse();

    // A host screen always has a record; an unsaved one is served without an id.
    if ($record !== null) {
        $this->get(route('test-host', ['key' => $key, 'id' => $record->exists ? $record->getKey() : null]))
            ->assertOk()
            ->assertDontSee($testId, false);
    }
})->with(visibilityTargets())->with(visibilityConditions());

it('authorizes through its own check, never permission() or public()', function (string $key, Closure $extension): void {
    $definition = $extension();

    expect($definition->permission)->toBeNull()
        ->and($definition->public)->toBeFalse();
})->with(visibilityTargets());

it('runs no query for a missing or unsaved record', function (string $key, Closure $extension): void {
    $definition = $extension();
    $unsaved = $key === 'roster.user' ? new User : new Organization;

    DB::enableQueryLog();
    DB::flushQueryLog();

    visibilityAllows($definition, $key, null);
    visibilityAllows($definition, $key, $unsaved);

    expect(DB::getQueryLog())->toBe([]);
})->with(visibilityTargets());

it('runs no query while stored domains are switched off', function (string $key, Closure $extension): void {
    $definition = $extension();
    $record = visibilityRecord($key);
    SwitchableRedirectDomainPolicy::$queries = true;
    config()->set('cortex.redirect_domains.enabled', false);

    DB::enableQueryLog();
    DB::flushQueryLog();

    visibilityAllows($definition, $key, $record);

    expect(DB::getQueryLog())->toBe([]);
})->with(visibilityTargets());

it('adds no more than one policy query per record to a host page', function (): void {
    $users = [
        $this->viewer,
        User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']),
        User::query()->create(['name' => 'Grace', 'email' => 'grace@example.com', 'password' => 'secret']),
    ];

    $asideQueries = function () use ($users): int {
        $request = Request::create('/test-host');
        $request->setUserResolver(fn (): mixed => auth()->user());

        DB::enableQueryLog();
        DB::flushQueryLog();

        foreach ($users as $user) {
            app()->forgetScopedInstances();

            $resolved = app(ScreenExtensions::class)->resolve(Screen::standard('roster.user', Screen::DETAIL)->record($user), $request);

            expect($resolved->sections('aside'))->toHaveCount(1);
        }

        return count(DB::getQueryLog());
    };

    // The bundled policy answers viewAny without the database.
    expect($asideQueries())->toBe(0);

    // A policy of the app's own that queries once is asked once per record.
    SwitchableRedirectDomainPolicy::$queries = true;

    expect($asideQueries())->toBeLessThanOrEqual(count($users));
});

it('hides the section and keeps the host page up when the policy throws', function (): void {
    SwitchableRedirectDomainPolicy::$throws = true;

    $this->get(route('test-host', ['key' => 'roster.user', 'id' => $this->viewer->getKey()]))
        ->assertOk()
        ->assertSee('data-testid="test-host-content"', false)
        ->assertDontSee('data-testid="section-cortex.redirect-domains"', false)
        ->assertDontSee('data-testid="screen-aside"', false);

    $organization = Organization::query()->create(['name' => 'Acme Inc']);

    $this->get(route('test-host', ['key' => 'roster.organization', 'id' => $organization->getKey()]))
        ->assertOk()
        ->assertDontSee('data-testid="tab-cortex.mcp"', false);

    expect(visibilityAllows(Atrium::screens()->tabs('roster.organization')[0], 'roster.organization', $organization))->toBeFalse();
});
