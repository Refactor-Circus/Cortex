<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\SwitchableRedirectDomainPolicy;
use Workbench\App\Models\Organization;
use Workbench\App\Models\User;
use Workbench\App\Policies\OrganizationPolicy;
use Workbench\App\Policies\UserPolicy;

/**
 * The redirect-domain UI on another package's screens, moved from Roster:
 * the `cortex.mcp` tab on `roster.organization` and the
 * `cortex.redirect-domains` section in the `aside` of `roster.user`, both
 * served by the workbench test host. The viewer is a signed-in workbench
 * User whom the workbench owner policies allow to `update` the owner.
 */
beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);
    Gate::define('viewAtrium', fn (mixed $user = null): bool => true);

    SwitchableRedirectDomainPolicy::reset();
    Gate::policy(RedirectDomainModel::class, SwitchableRedirectDomainPolicy::class);

    $this->morphMapBefore = Relation::$morphMap;

    $this->viewer = User::query()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'secret']);
    $this->organization = Organization::query()->create(['name' => 'Acme Inc']);

    $this->actingAs($this->viewer);
});

afterEach(function (): void {
    Relation::$morphMap = $this->morphMapBefore;

    SwitchableRedirectDomainPolicy::reset();
});

function ownedHostUrl(string $key, Model $record, ?string $tab = null): string
{
    return route('test-host', array_filter(['key' => $key, 'id' => $record->getKey(), 'tab' => $tab]));
}

/**
 * The hidden fields the screen's forms post for their record.
 *
 * @return array{owner_type: string, owner_id: string}
 */
function ownedFields(Model $owner): array
{
    return ['owner_type' => $owner->getMorphClass(), 'owner_id' => (string) $owner->getKey()];
}

function ownedDomain(?Model $owner, string $domain = 'claude.ai'): RedirectDomainModel
{
    return app(CreateRedirectDomainAction::class)->execute(new CreateRedirectDomainData(domain: $domain), $owner);
}

/**
 * Atrium keeps a request's screen answers and data in a scoped service,
 * which only a new request (or job) forgets; tests make several requests in
 * one application, so forget them before each page view.
 */
function ownedPage(string $url): TestResponse
{
    app()->forgetScopedInstances();

    return test()->get($url);
}

it('shows the organization tab and adds, lists and removes its domains', function (): void {
    $host = ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp');

    ownedPage(ownedHostUrl('roster.organization', $this->organization))
        ->assertOk()
        ->assertSee('data-testid="tab-cortex.mcp"', false);

    ownedPage($host)
        ->assertOk()
        ->assertSee('data-testid="tab-panel-cortex.mcp"', false)
        ->assertSee(__('cortex::cortex.owned_redirect_domains_hint'))
        ->assertSee('data-testid="add-redirect-domain"', false)
        ->assertSee('action="'.route('atrium.cortex.redirect-domains.store').'"', false)
        ->assertSee('name="owner_type" value="'.Organization::class.'"', false)
        ->assertSee('name="owner_id" value="'.$this->organization->getKey().'"', false)
        ->assertSee(__('cortex::cortex.no_redirect_domains'))
        ->assertDontSee('data-testid="owned-redirect-domains"', false)
        ->assertDontSee('data-testid="test-host-content"', false);

    $this->from($host)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai', ...ownedFields($this->organization)])
        ->assertRedirect($host);

    $stored = RedirectDomainModel::query()->sole();

    expect($stored->domain)->toBe('https://claude.ai')
        ->and($stored->owner_type)->toBe(Organization::class)
        ->and($stored->owner_id)->toBe((string) $this->organization->getKey());

    ownedPage($host)
        ->assertOk()
        ->assertSee('data-testid="owned-redirect-domains"', false)
        ->assertSee('https://claude.ai')
        ->assertSee('data-testid="remove-redirect-domain"', false)
        ->assertSee('action="'.route('atrium.cortex.redirect-domains.destroy', $stored->id).'"', false)
        ->assertDontSee(__('cortex::cortex.no_redirect_domains'));

    $this->from($host)
        ->delete(route('atrium.cortex.redirect-domains.destroy', $stored->id), ownedFields($this->organization))
        ->assertRedirect($host);

    expect(RedirectDomainModel::query()->exists())->toBeFalse();
});

it('lists only the owner\'s own domains', function (): void {
    $other = Organization::query()->create(['name' => 'Other Inc']);
    ownedDomain($this->organization, 'claude.ai');
    ownedDomain($other, 'chatgpt.com');
    ownedDomain(null, 'perplexity.ai');

    ownedPage(ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp'))
        ->assertOk()
        ->assertSee('https://claude.ai')
        ->assertDontSee('https://chatgpt.com')
        ->assertDontSee('https://perplexity.ai');
});

it('hides the tab and refuses ?tab= when the policy denies viewAny', function (): void {
    SwitchableRedirectDomainPolicy::$denies = ['viewAny'];

    ownedPage(ownedHostUrl('roster.organization', $this->organization))
        ->assertOk()
        ->assertDontSee('data-testid="tab-cortex.mcp"', false);

    ownedPage(ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp'))->assertForbidden();
});

it('refuses a post when the policy denies create', function (): void {
    SwitchableRedirectDomainPolicy::$denies = ['create'];
    $host = ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp');

    $this->from($host)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai', ...ownedFields($this->organization)])
        ->assertForbidden();

    expect(RedirectDomainModel::query()->exists())->toBeFalse();
});

it('lists the domains read-only when the owner\'s own policy denies update', function (): void {
    OrganizationPolicy::$deniesUpdate = true;
    $domain = ownedDomain($this->organization);
    $host = ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp');

    ownedPage($host)
        ->assertOk()
        ->assertSee('data-testid="owned-redirect-domains"', false)
        ->assertSee('https://claude.ai')
        ->assertDontSee('data-testid="add-redirect-domain"', false)
        ->assertDontSee('data-testid="remove-redirect-domain"', false);

    $this->from($host)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', ...ownedFields($this->organization)])
        ->assertForbidden();

    $this->from($host)
        ->delete(route('atrium.cortex.redirect-domains.destroy', $domain->id), ownedFields($this->organization))
        ->assertForbidden();

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://claude.ai']);
});

it('answers 404 when removing another owner\'s domain', function (): void {
    $other = Organization::query()->create(['name' => 'Other Inc']);
    $domainOfOther = ownedDomain($other);

    $this->from(ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp'))
        ->delete(route('atrium.cortex.redirect-domains.destroy', $domainOfOther->id), ownedFields($this->organization))
        ->assertNotFound();

    expect(RedirectDomainModel::query()->whereKey($domainOfOther->id)->exists())->toBeTrue();
});

it('sends an invalid domain back to the host page with an error', function (): void {
    $host = ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp');

    $this->from($host)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'not a domain', ...ownedFields($this->organization)])
        ->assertRedirect($host)
        ->assertSessionHasErrors('domain');

    expect(RedirectDomainModel::query()->exists())->toBeFalse();
});

it('renders the user aside section and adds and removes its domains', function (): void {
    $host = ownedHostUrl('roster.user', $this->viewer);

    ownedPage($host)
        ->assertOk()
        ->assertSee('data-testid="screen-aside"', false)
        ->assertSee('data-testid="section-cortex.redirect-domains"', false)
        ->assertSee('data-testid="add-redirect-domain"', false)
        ->assertSee('name="owner_type" value="'.User::class.'"', false)
        ->assertSee('data-testid="test-host-content"', false);

    $this->from($host)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai', ...ownedFields($this->viewer)])
        ->assertRedirect($host);

    $stored = RedirectDomainModel::query()->sole();

    expect($stored->owner_type)->toBe(User::class)
        ->and($stored->owner_id)->toBe((string) $this->viewer->getKey());

    ownedPage($host)
        ->assertOk()
        ->assertSee('https://claude.ai')
        ->assertSee('data-testid="remove-redirect-domain"', false);

    $this->from($host)
        ->delete(route('atrium.cortex.redirect-domains.destroy', $stored->id), ownedFields($this->viewer))
        ->assertRedirect($host);

    expect(RedirectDomainModel::query()->exists())->toBeFalse();
});

it('keeps the user aside read-only when the user policy denies update', function (): void {
    UserPolicy::$deniesUpdate = true;
    ownedDomain($this->viewer);

    ownedPage(ownedHostUrl('roster.user', $this->viewer))
        ->assertOk()
        ->assertSee('https://claude.ai')
        ->assertDontSee('data-testid="add-redirect-domain"', false)
        ->assertDontSee('data-testid="remove-redirect-domain"', false);
});

it('shows the list without the add form or remove buttons when create and delete are denied', function (): void {
    SwitchableRedirectDomainPolicy::$denies = ['create', 'delete'];
    ownedDomain($this->organization);
    ownedDomain($this->viewer);

    ownedPage(ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp'))
        ->assertOk()
        ->assertSee('data-testid="owned-redirect-domains"', false)
        ->assertSee('https://claude.ai')
        ->assertDontSee('data-testid="add-redirect-domain"', false)
        ->assertDontSee('data-testid="remove-redirect-domain"', false);

    ownedPage(ownedHostUrl('roster.user', $this->viewer))
        ->assertOk()
        ->assertSee('data-testid="owned-redirect-domains"', false)
        ->assertDontSee('data-testid="add-redirect-domain"', false)
        ->assertDontSee('data-testid="remove-redirect-domain"', false);
});

it('renders neither the tab nor the section while stored domains are switched off', function (): void {
    config()->set('cortex.redirect_domains.enabled', false);

    ownedPage(ownedHostUrl('roster.organization', $this->organization))
        ->assertOk()
        ->assertDontSee('data-testid="tab-cortex.mcp"', false);

    ownedPage(ownedHostUrl('roster.user', $this->viewer))
        ->assertOk()
        ->assertDontSee('data-testid="section-cortex.redirect-domains"', false);
});

it('names the owner by its morph alias', function (): void {
    Relation::morphMap(['organization' => Organization::class]);
    $host = ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp');

    ownedPage($host)
        ->assertOk()
        ->assertSee('name="owner_type" value="organization"', false);

    $this->from($host)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai', ...ownedFields($this->organization)])
        ->assertRedirect($host);

    $stored = RedirectDomainModel::query()->sole();

    expect($stored->owner_type)->toBe('organization')
        ->and($stored->owner_id)->toBe((string) $this->organization->getKey());

    ownedPage($host)
        ->assertOk()
        ->assertSee('https://claude.ai')
        ->assertSee('data-testid="remove-redirect-domain"', false);

    $this->from($host)
        ->delete(route('atrium.cortex.redirect-domains.destroy', $stored->id), ownedFields($this->organization))
        ->assertRedirect($host);

    expect(RedirectDomainModel::query()->exists())->toBeFalse();

    $this->from($host)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai', 'owner_type' => 'unregistered', 'owner_id' => (string) $this->organization->getKey()])
        ->assertNotFound();

    expect(RedirectDomainModel::query()->exists())->toBeFalse();
});

it('asks the owner\'s policy once per domain without loading the owner again', function (): void {
    ownedDomain($this->organization, 'claude.ai');
    ownedDomain($this->organization, 'chatgpt.com');
    ownedDomain($this->organization, 'perplexity.ai');

    DB::enableQueryLog();
    DB::flushQueryLog();

    ownedPage(ownedHostUrl('roster.organization', $this->organization, 'cortex.mcp'))
        ->assertOk()
        ->assertSee('https://perplexity.ai');

    $ownerLookups = array_filter(
        DB::getQueryLog(),
        fn (array $query): bool => str_contains($query['query'], (new Organization)->getTable()),
    );

    // The host loads its record once; the domains' owner is the record.
    expect($ownerLookups)->toHaveCount(1);
});
