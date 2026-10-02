<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use JayI\Atrium\Navigation\NavigationRegistry;
use JayI\Atrium\Navigation\NavItem;
use JayI\Cortex\Atrium\CortexPlugin;
use JayI\Cortex\Features\CortexSupportFeature;
use Laravel\Pennant\Feature;
use Workbench\App\Models\User;

beforeEach(function (): void {
    Gate::define('viewAtrium', fn (mixed $user = null): bool => true);
});

function pennantUser(): User
{
    return (new User)->forceFill(['id' => 1, 'name' => 'Viewer', 'email' => 'viewer@example.com']);
}

/**
 * @return array<int, string>
 */
function pennantNavigation(?User $user = null): array
{
    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): ?User => $user);

    return array_map(fn (NavItem $item): string => $item->label, app(NavigationRegistry::class)->items($request));
}

/**
 * Off until its global value is set, to show the class can be overridden.
 */
class OffCortexSupportFeature extends CortexSupportFeature
{
    protected function default(): bool
    {
        return false;
    }
}

it('gates cortex on the bundled feature by default', function (): void {
    expect(app(CortexPlugin::class)->features())->toBe([CortexSupportFeature::class]);
});

it('skips feature classes that are not installed', function (): void {
    config()->set('cortex.atrium.features', ['App\\Features\\Missing', 'plain-feature']);

    expect(app(CortexPlugin::class)->features())->toBe(['plain-feature']);
});

it('shows cortex until the feature is turned off globally', function (): void {
    $user = pennantUser();

    expect(pennantNavigation($user))->toContain('Virtual agents');

    $this->actingAs($user)->get(route('atrium.cortex.virtual-agents.index'))->assertOk();

    Feature::for(null)->deactivate(CortexSupportFeature::class);

    expect(pennantNavigation($user))->not->toContain('Virtual agents');

    $this->actingAs($user)->get(route('atrium.cortex.virtual-agents.index'))->assertNotFound();
    $this->actingAs($user)->get(route('atrium.cortex.tools.index'))->assertNotFound();
});

it('only counts the global value, leaving per-user access to the policies', function (): void {
    $user = pennantUser();

    Feature::for($user)->deactivate(CortexSupportFeature::class);

    expect(pennantNavigation($user))->toContain('Virtual agents');
});

it('uses a subclass named in the config instead', function (): void {
    config()->set('cortex.atrium.features', [OffCortexSupportFeature::class]);

    expect(pennantNavigation(pennantUser()))->not->toContain('Virtual agents');

    Feature::for(null)->activate(OffCortexSupportFeature::class);

    expect(pennantNavigation(pennantUser()))->toContain('Virtual agents');
});
