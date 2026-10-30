<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use Workbench\App\Models\Organization;
use Workbench\App\Models\User;
use Workbench\App\Policies\OrganizationPolicy;
use Workbench\App\Policies\UserPolicy;

/**
 * @return array<string, array{0: string, 1: Closure(): Model}>
 */
function testHostScreens(): array
{
    return [
        'roster.organization' => ['roster.organization', fn (): Model => Organization::query()->create(['name' => 'Acme Inc'])],
        'roster.user' => ['roster.user', fn (): Model => User::query()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'secret'])],
        'acme.account' => ['acme.account', fn (): Model => Organization::query()->create(['name' => 'Acme Account'])],
    ];
}

it('renders the workbench screen for its key and record', function (string $key, Closure $record): void {
    $model = $record();

    $this->get(route('test-host', ['key' => $key, 'id' => $model->getKey()]))
        ->assertOk()
        ->assertSee($key)
        ->assertSee('data-testid="test-host-content"', false)
        ->assertSee((string) $model->getAttribute('name'))
        ->assertDontSee('data-testid="screen-aside"', false);
})->with(testHostScreens());

it('declares the screen under the key in its route', function (string $key, Closure $record): void {
    Atrium::screens()->tab($key, TabDefinition::make('acme.extra')->label('Extra')->view('workbench::test-host')->public());
    Atrium::screens()->tab('acme.elsewhere', TabDefinition::make('acme.other')->label('Other')->view('workbench::test-host')->public());

    $this->get(route('test-host', ['key' => $key, 'id' => $record()->getKey()]))
        ->assertOk()
        ->assertSee('data-testid="tab-acme.extra"', false)
        ->assertDontSee('data-testid="tab-acme.other"', false);
})->with(testHostScreens());

it('renders an unsaved record when no id is given', function (): void {
    $this->get(route('test-host', ['key' => 'roster.organization']))
        ->assertOk()
        ->assertSee('data-testid="test-host-content"', false);
});

it('answers 404 for an unknown record', function (): void {
    $this->get(route('test-host', ['key' => 'roster.organization', 'id' => 999]))->assertNotFound();
});

it('lets any signed-in user update an owner until a test denies it', function (): void {
    $organization = Organization::query()->create(['name' => 'Acme Inc']);
    $user = User::query()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'secret']);

    expect(Gate::forUser($user)->allows('update', $organization))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $user))->toBeTrue()
        ->and(Gate::forUser(null)->allows('update', $organization))->toBeFalse()
        ->and(Gate::forUser(null)->allows('update', $user))->toBeFalse();

    OrganizationPolicy::$deniesUpdate = true;
    UserPolicy::$deniesUpdate = true;

    expect(Gate::forUser($user)->allows('update', $organization))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $user))->toBeFalse();
});

it('starts every test with the owner policies allowing update', function (): void {
    expect(OrganizationPolicy::$deniesUpdate)->toBeFalse()
        ->and(UserPolicy::$deniesUpdate)->toBeFalse();
});
