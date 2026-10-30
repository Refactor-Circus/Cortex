<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Tests\Fixtures\OwnerPolicyFixture;

/**
 * The dashboard's store and destroy routes take an optional owner, so a host
 * screen's form adds and removes its record's domains. The owner is a
 * virtual agent whose policy is the fixture (a host's own policy), and the
 * request host is `http://localhost`.
 */
beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);
    Gate::define('viewAtrium', fn (mixed $user = null): bool => true);

    OwnerPolicyFixture::reset();
    Gate::policy(VirtualAgentModel::class, OwnerPolicyFixture::class);

    $this->morphMapBefore = Relation::$morphMap;

    $this->actingAs(new GenericUser(['id' => 1, 'name' => 'Owner admin', 'email' => 'admin@example.com']));
});

afterEach(function (): void {
    Relation::$morphMap = $this->morphMapBefore;

    OwnerPolicyFixture::reset();
});

/**
 * The hidden fields a host screen's form posts for its record.
 *
 * @return array{owner_type: string, owner_id: string}
 */
function ownerFields(Model $owner): array
{
    return ['owner_type' => $owner->getMorphClass(), 'owner_id' => (string) $owner->getKey()];
}

function ownerDomain(?Model $owner, string $domain = 'claude.ai'): RedirectDomainModel
{
    return app(CreateRedirectDomainAction::class)->execute(new CreateRedirectDomainData(domain: $domain), $owner);
}

const OWNER_HOST_PAGE = 'http://localhost/test-host/acme.account/1?tab=cortex.mcp';

it('stores a domain for an owner named by its class and goes back to the host page', function (): void {
    $owner = VirtualAgentModel::factory()->create();

    $this->from(OWNER_HOST_PAGE)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai', ...ownerFields($owner)])
        ->assertRedirect(OWNER_HOST_PAGE)
        ->assertSessionHas('status');

    $stored = RedirectDomainModel::query()->sole();

    expect($stored->domain)->toBe('https://claude.ai')
        ->and($stored->owner_type)->toBe(VirtualAgentModel::class)
        ->and($stored->owner_id)->toBe((string) $owner->getKey());
});

it('removes an owner\'s domain and goes back to the host page', function (): void {
    $owner = VirtualAgentModel::factory()->create();
    $domain = ownerDomain($owner);

    $this->from(OWNER_HOST_PAGE)
        ->delete(route('atrium.cortex.redirect-domains.destroy', $domain->id), ownerFields($owner))
        ->assertRedirect(OWNER_HOST_PAGE)
        ->assertSessionHas('status');

    expect(RedirectDomainModel::query()->exists())->toBeFalse();
});

it('takes the owner by its morph alias', function (): void {
    Relation::morphMap(['owner-agent' => VirtualAgentModel::class]);
    $owner = VirtualAgentModel::factory()->create();

    expect(ownerFields($owner)['owner_type'])->toBe('owner-agent');

    $this->from(OWNER_HOST_PAGE)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai', ...ownerFields($owner)])
        ->assertRedirect(OWNER_HOST_PAGE);

    $stored = RedirectDomainModel::query()->sole();

    expect($stored->owner_type)->toBe('owner-agent')
        ->and($stored->owner_id)->toBe((string) $owner->getKey());

    $this->from(OWNER_HOST_PAGE)
        ->delete(route('atrium.cortex.redirect-domains.destroy', $stored->id), ownerFields($owner))
        ->assertRedirect(OWNER_HOST_PAGE);

    expect(RedirectDomainModel::query()->exists())->toBeFalse();
});

it('refuses owner types RedirectDomains::owner() does not resolve, writing nothing', function (string $type): void {
    $owner = VirtualAgentModel::factory()->create();
    $domain = ownerDomain($owner);

    $this->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', 'owner_type' => $type, 'owner_id' => '1'])
        ->assertNotFound();
    $this->delete(route('atrium.cortex.redirect-domains.destroy', $domain->id), ['owner_type' => $type, 'owner_id' => (string) $owner->getKey()])
        ->assertNotFound();

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://claude.ai']);
})->with([
    'a non-model class' => [stdClass::class],
    'a non-Eloquent class' => [Collection::class],
    'the abstract Eloquent base' => [Model::class],
    'a missing class' => ['Nope\\Missing'],
    'an unknown alias' => ['unknown-owner'],
]);

it('refuses a malformed owner id, writing nothing', function (mixed $id, int $status): void {
    $owner = VirtualAgentModel::factory()->create();
    $domain = ownerDomain($owner);

    $this->postJson(route('atrium.cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', 'owner_type' => VirtualAgentModel::class, 'owner_id' => $id])
        ->assertStatus($status);
    $this->deleteJson(route('atrium.cortex.redirect-domains.destroy', $domain->id), ['owner_type' => VirtualAgentModel::class, 'owner_id' => $id])
        ->assertStatus($status);

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://claude.ai']);
})->with([
    'an array' => [['1'], 422],
    'too long' => [str_repeat('x', 256), 422],
    'an unknown key' => ['missing', 404],
]);

it('answers 422 on the missing field for a partial owner, writing nothing', function (array $fields, string $missing): void {
    $owner = VirtualAgentModel::factory()->create();
    $domain = ownerDomain($owner);
    $fields = array_map(fn (string $value): string => str_replace(':id', (string) $owner->getKey(), $value), $fields);

    $this->postJson(route('atrium.cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', ...$fields])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($missing);
    $this->deleteJson(route('atrium.cortex.redirect-domains.destroy', $domain->id), $fields)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($missing);

    // A form post gets the same error back on the page it came from.
    $this->from(OWNER_HOST_PAGE)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', ...$fields])
        ->assertRedirect(OWNER_HOST_PAGE)
        ->assertSessionHasErrors($missing);

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://claude.ai']);
})->with([
    'a type without an id' => [['owner_type' => VirtualAgentModel::class], 'owner_id'],
    'an id without a type' => [['owner_id' => ':id'], 'owner_type'],
    'an empty id' => [['owner_type' => VirtualAgentModel::class, 'owner_id' => ''], 'owner_id'],
    'an empty type' => [['owner_type' => '', 'owner_id' => ':id'], 'owner_type'],
]);

it('answers 404 for an owner that does not exist', function (): void {
    $owner = VirtualAgentModel::factory()->create();
    $domain = ownerDomain($owner);
    $missing = ['owner_type' => VirtualAgentModel::class, 'owner_id' => '01JZZZZZZZZZZZZZZZZZZZZZZZ'];

    $this->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', ...$missing])->assertNotFound();
    $this->delete(route('atrium.cortex.redirect-domains.destroy', $domain->id), $missing)->assertNotFound();

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://claude.ai']);
});

it('finds the domain among the named owner\'s only', function (): void {
    $ownerA = VirtualAgentModel::factory()->create();
    $ownerB = VirtualAgentModel::factory()->create();
    $domainOfB = ownerDomain($ownerB);
    $global = ownerDomain(null, 'perplexity.ai');

    $this->delete(route('atrium.cortex.redirect-domains.destroy', $domainOfB->id), ownerFields($ownerA))->assertNotFound();
    $this->delete(route('atrium.cortex.redirect-domains.destroy', $global->id), ownerFields($ownerA))->assertNotFound();

    expect(RedirectDomainModel::query()->whereKey([$domainOfB->id, $global->id])->count())->toBe(2);
});

it('authorizes create with the owner and delete on the domain', function (): void {
    OwnerPolicyFixture::$allowsUpdate = false;
    $owner = VirtualAgentModel::factory()->create();
    $domain = ownerDomain($owner);

    $this->from(OWNER_HOST_PAGE)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', ...ownerFields($owner)])
        ->assertForbidden();
    $this->from(OWNER_HOST_PAGE)
        ->delete(route('atrium.cortex.redirect-domains.destroy', $domain->id), ownerFields($owner))
        ->assertForbidden();

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://claude.ai'])
        ->and(OwnerPolicyFixture::$asked)->toBe(2);
});

it('goes back only to a page on the request\'s own scheme, host and port', function (string $previous, ?string $expected): void {
    $owner = VirtualAgentModel::factory()->create();
    $expected ??= route('atrium.cortex.redirect-domains.index');

    $this->from($previous)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai', ...ownerFields($owner)])
        ->assertRedirect($expected);

    $domain = RedirectDomainModel::query()->sole();

    $this->from($previous)
        ->delete(route('atrium.cortex.redirect-domains.destroy', $domain->id), ownerFields($owner))
        ->assertRedirect($expected);
})->with([
    'the same host' => [OWNER_HOST_PAGE, OWNER_HOST_PAGE],
    'the same host, without a query' => ['http://localhost/test-host/roster.user/1', 'http://localhost/test-host/roster.user/1'],
    'a protocol-relative path stays on the own host' => ['http://localhost//evil.test/steal', 'http://localhost//evil.test/steal'],
    'a foreign host' => ['https://evil.test/test-host/acme.account/1', null],
    'a lookalike host' => ['http://localhost.evil.test/test-host/acme.account/1', null],
    'a userinfo form' => ['http://localhost@evil.test/test-host/acme.account/1', null],
    'a scheme mismatch' => ['https://localhost/test-host/acme.account/1', null],
    'another port' => ['http://localhost:8080/test-host/acme.account/1', null],
]);

it('falls back to the redirect-domains screen without a referrer', function (): void {
    $owner = VirtualAgentModel::factory()->create();

    $this->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai', ...ownerFields($owner)])
        ->assertRedirect(route('atrium.cortex.redirect-domains.index'));

    $domain = RedirectDomainModel::query()->sole();

    $this->delete(route('atrium.cortex.redirect-domains.destroy', $domain->id), ownerFields($owner))
        ->assertRedirect(route('atrium.cortex.redirect-domains.index'));

    expect(RedirectDomainModel::query()->exists())->toBeFalse();
});

it('keeps sending writes without an owner to the redirect-domains screen', function (): void {
    $this->from(OWNER_HOST_PAGE)
        ->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai'])
        ->assertRedirect(route('atrium.cortex.redirect-domains.index'));

    $domain = RedirectDomainModel::query()->sole();

    expect($domain->owner_type)->toBeNull();

    $this->from(OWNER_HOST_PAGE)
        ->delete(route('atrium.cortex.redirect-domains.destroy', $domain->id))
        ->assertRedirect(route('atrium.cortex.redirect-domains.index'));

    expect(OwnerPolicyFixture::$asked)->toBe(0);
});
