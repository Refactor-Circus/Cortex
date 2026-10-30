<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Cortex\Atrium\Screens;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\GrantedVirtualAgentPolicy;

/**
 * An extension's `permission()` on a Cortex screen is asked of the policies
 * in `cortex.policies`: against the screen's record, a row inside a row slot,
 * or else the screen's subject class. The run page has neither, so it grants
 * none.
 */
beforeEach(function (): void {
    Gate::define('viewAtrium', fn (mixed $user = null): bool => true);
    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);

    View::addNamespace('cortex-tests', dirname(__DIR__, 2).'/Fixtures/views');
});

/**
 * A signed-in user granted exactly these abilities by the fixture policy.
 *
 * @param  list<string>  $abilities
 */
function accessClosureUser(array $abilities): GenericUser
{
    return new GenericUser(['id' => 1, 'name' => 'Viewer', 'email' => 'viewer@example.com', 'abilities' => $abilities]);
}

function accessClosureSection(string $key, ?string $permission): SectionDefinition
{
    $section = SectionDefinition::make($key)
        ->label($key)
        ->view('cortex-tests::screen-probe')
        ->resolve(fn (): array => ['probe' => $key]);

    return $permission === null ? $section->public() : $section->permission($permission);
}

function accessClosureRequest(?GenericUser $user): Request
{
    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): ?GenericUser => $user);

    return $request;
}

function accessClosureTestId(string $key): string
{
    return 'data-testid="section-'.$key.'"';
}

function accessClosureAgent(): VirtualAgentModel
{
    return VirtualAgentModel::factory()->published()->create(['name' => 'Helper', 'slug' => 'helper']);
}

it('shows a view section on a virtual agent the policy lets the viewer view', function (): void {
    accessClosureAgent();
    Atrium::screens()->section('cortex.virtual-agent', 'before', accessClosureSection('acme.view', 'view'));
    Atrium::screens()->section('cortex.virtual-agent', 'before', accessClosureSection('acme.update', 'update'));

    $this->actingAs(accessClosureUser(['virtual-agents.view']))
        ->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertOk()
        ->assertSee(accessClosureTestId('acme.view'), false)
        // The same closure asks `update`, which this viewer lacks.
        ->assertDontSee(accessClosureTestId('acme.update'), false);
});

it('hides a view section on a virtual agent the policy denies', function (): void {
    $agent = accessClosureAgent();
    Atrium::screens()->section('cortex.virtual-agent', 'before', accessClosureSection('acme.view', 'view'));

    $screen = Screens::declare('cortex.virtual-agent', Screen::DETAIL, $agent, null);

    expect($screen->permits('view', accessClosureRequest(accessClosureUser([])), $agent))->toBeFalse()
        ->and($screen->permits('view', accessClosureRequest(accessClosureUser(['virtual-agents.view'])), $agent))->toBeTrue()
        ->and(app(ScreenExtensions::class)->resolve($screen, accessClosureRequest(accessClosureUser([])))->sections('before'))->toBe([]);
});

it('asks a viewAny section on the virtual agents list of the class', function (): void {
    Atrium::screens()->section('cortex.virtual-agents', 'before', accessClosureSection('acme.view-any', 'viewAny'));

    $screen = Screens::declare('cortex.virtual-agents', Screen::LIST, null, VirtualAgentModel::class);

    expect($screen->permits('viewAny', accessClosureRequest(accessClosureUser([])), null))->toBeFalse()
        ->and($screen->permits('viewAny', accessClosureRequest(accessClosureUser(['virtual-agents.viewAny'])), null))->toBeTrue()
        ->and(app(ScreenExtensions::class)->resolve($screen, accessClosureRequest(accessClosureUser([])))->sections('before'))->toBe([]);

    // Answers are remembered per request, so forget the denial above.
    app()->forgetScopedInstances();

    $this->actingAs(accessClosureUser(['virtual-agents.viewAny']))
        ->get(route('atrium.cortex.virtual-agents.index'))
        ->assertOk()
        ->assertSee(accessClosureTestId('acme.view-any'), false);
});

it('asks a row section on the virtual agents list of its row', function (): void {
    accessClosureAgent();
    Atrium::screens()->section('cortex.virtual-agents', 'row.actions', accessClosureSection('acme.row-view', 'view'));

    $this->actingAs(accessClosureUser(['virtual-agents.viewAny']))
        ->get(route('atrium.cortex.virtual-agents.index'))
        ->assertOk()
        ->assertSee('Helper')
        ->assertDontSee(accessClosureTestId('acme.row-view'), false);

    // Answers are remembered per request.
    app()->forgetScopedInstances();

    $this->actingAs(accessClosureUser(['virtual-agents.viewAny', 'virtual-agents.view']))
        ->get(route('atrium.cortex.virtual-agents.index'))
        ->assertOk()
        ->assertSee(accessClosureTestId('acme.row-view'), false);
});

it('never shows a permission section on the run page', function (): void {
    // Every ability allowed, so only the missing record and subject deny.
    Gate::before(fn (): bool => true);

    Atrium::screens()->section('cortex.run', 'before', accessClosureSection('acme.run-view-any', 'viewAny'));
    Atrium::screens()->section('cortex.run', 'before', accessClosureSection('acme.run-public', null));

    $screen = Screens::declare('cortex.run', Screen::DETAIL, null, null);

    expect($screen->permits('viewAny', accessClosureRequest(accessClosureUser(['virtual-agents.viewAny'])), null))->toBeFalse()
        ->and($screen->permits('view', accessClosureRequest(null), null))->toBeFalse();

    $this->actingAs(accessClosureUser(['virtual-agents.viewAny']))
        ->get(route('atrium.cortex.run'))
        ->assertOk()
        ->assertSee(accessClosureTestId('acme.run-public'), false)
        ->assertDontSee(accessClosureTestId('acme.run-view-any'), false);
});
