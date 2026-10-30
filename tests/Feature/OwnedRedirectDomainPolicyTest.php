<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\CreateRedirectDomainTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\DeleteRedirectDomainTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Tests\Fixtures\OwnerPolicyFixture;

/**
 * For a domain with an owner, the bundled RedirectDomainPolicy asks the
 * owner's own policy: `create` and `delete` need a signed-in user who may
 * `update` the owner. The owner here is a virtual agent whose policy is the
 * fixture, standing in for a host's organization or user policy. Every case
 * runs over the Gate, the JSON API and MCP.
 */
beforeEach(function (): void {
    OwnerPolicyFixture::reset();

    Gate::policy(VirtualAgentModel::class, OwnerPolicyFixture::class);
});

afterEach(function (): void {
    OwnerPolicyFixture::reset();
});

function ownedPolicyOwner(): VirtualAgentModel
{
    return VirtualAgentModel::factory()->create();
}

function ownedPolicyDomain(?VirtualAgentModel $owner, string $domain = 'claude.ai'): RedirectDomainModel
{
    return app(CreateRedirectDomainAction::class)->execute(new CreateRedirectDomainData(domain: $domain), $owner);
}

/**
 * The input naming an owner, as the JSON API and MCP take it.
 *
 * @return array{owner_type: string, owner_id: string}
 */
function ownedPolicyOwnerInput(VirtualAgentModel $owner): array
{
    return ['owner_type' => $owner->getMorphClass(), 'owner_id' => (string) $owner->getKey()];
}

function ownedPolicyUser(): GenericUser
{
    return new GenericUser(['id' => 1, 'name' => 'Owner admin', 'email' => 'admin@example.com']);
}

it('refuses a guest creating or deleting an owned domain, even when the owner policy would allow it', function (): void {
    $owner = ownedPolicyOwner();
    $domain = ownedPolicyDomain($owner);

    expect(Gate::forUser(null)->allows('create', [RedirectDomainModel::class, $owner]))->toBeFalse()
        ->and(Gate::forUser(null)->allows('delete', $domain))->toBeFalse()
        ->and(Gate::forUser(null)->allows('viewAny', [RedirectDomainModel::class, $owner]))->toBeTrue();

    // A refused guest gets 401 over HTTP (authorization standard), a
    // refused signed-in user 403.
    $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', ...ownedPolicyOwnerInput($owner)])
        ->assertUnauthorized();
    $this->deleteJson(route('cortex.redirect-domains.destroy', $domain->id))->assertUnauthorized();

    mcpTool(CreateRedirectDomainTool::class, ['domain' => 'chatgpt.com', ...ownedPolicyOwnerInput($owner)])
        ->assertHasErrors(['Unauthorized.']);
    mcpTool(DeleteRedirectDomainTool::class, ['id' => $domain->id])->assertHasErrors(['Unauthorized.']);

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://claude.ai']);
});

it('refuses a signed-in user the owner policy does not let update the owner', function (): void {
    OwnerPolicyFixture::$allowsUpdate = false;
    $owner = ownedPolicyOwner();
    $domain = ownedPolicyDomain($owner);
    $user = ownedPolicyUser();
    $this->actingAs($user);

    expect(Gate::forUser($user)->allows('create', [RedirectDomainModel::class, $owner]))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $domain))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', [RedirectDomainModel::class, $owner]))->toBeTrue();

    $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', ...ownedPolicyOwnerInput($owner)])
        ->assertForbidden();
    $this->deleteJson(route('cortex.redirect-domains.destroy', $domain->id))->assertForbidden();

    mcpTool(CreateRedirectDomainTool::class, ['domain' => 'chatgpt.com', ...ownedPolicyOwnerInput($owner)])
        ->assertHasErrors(['Unauthorized.']);
    mcpTool(DeleteRedirectDomainTool::class, ['id' => $domain->id])->assertHasErrors(['Unauthorized.']);

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://claude.ai'])
        ->and(OwnerPolicyFixture::$asked)->toBeGreaterThan(0);
});

it('allows a signed-in user the owner policy lets update the owner', function (): void {
    $owner = ownedPolicyOwner();
    $user = ownedPolicyUser();
    $this->actingAs($user);

    $gateDomain = ownedPolicyDomain($owner, 'gate.example.com');

    expect(Gate::forUser($user)->allows('create', [RedirectDomainModel::class, $owner]))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $gateDomain))->toBeTrue();

    $id = $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'chatgpt.com', ...ownedPolicyOwnerInput($owner)])
        ->assertCreated()
        ->assertJsonPath('data.owner_id', (string) $owner->getKey())
        ->json('data.id');
    $this->deleteJson(route('cortex.redirect-domains.destroy', $id))->assertNoContent();

    mcpTool(CreateRedirectDomainTool::class, ['domain' => 'claude.ai', ...ownedPolicyOwnerInput($owner)])->assertOk();
    $mcp = RedirectDomainModel::query()->where('domain', 'https://claude.ai')->sole();

    expect($mcp->owner_id)->toBe((string) $owner->getKey());

    mcpTool(DeleteRedirectDomainTool::class, ['id' => $mcp->id])->assertOk();

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://gate.example.com']);
});

it('leaves domains without an owner open to guests and signed-in users alike', function (?GenericUser $user): void {
    OwnerPolicyFixture::$allowsUpdate = false;

    if ($user !== null) {
        $this->actingAs($user);
    }

    $gateDomain = ownedPolicyDomain(null, 'gate.example.com');

    expect(Gate::forUser($user)->allows('create', [RedirectDomainModel::class, null]))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', RedirectDomainModel::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $gateDomain))->toBeTrue();

    $id = $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'chatgpt.com'])
        ->assertCreated()
        ->assertJsonPath('data.owner_type', null)
        ->json('data.id');
    $this->deleteJson(route('cortex.redirect-domains.destroy', $id))->assertNoContent();

    mcpTool(CreateRedirectDomainTool::class, ['domain' => 'claude.ai'])->assertOk();
    $mcp = RedirectDomainModel::query()->where('domain', 'https://claude.ai')->sole();
    mcpTool(DeleteRedirectDomainTool::class, ['id' => $mcp->id])->assertOk();

    expect(RedirectDomainModel::query()->pluck('domain')->all())->toBe(['https://gate.example.com'])
        ->and(OwnerPolicyFixture::$asked)->toBe(0);
})->with([
    'a guest' => [null],
    'a signed-in user' => [fn (): GenericUser => ownedPolicyUser()],
]);

it('refuses deleting a domain whose owner row no longer exists', function (): void {
    $owner = ownedPolicyOwner();
    $domain = ownedPolicyDomain($owner);
    $owner->delete();
    $user = ownedPolicyUser();
    $this->actingAs($user);

    expect(Gate::forUser($user)->allows('delete', $domain->fresh()))->toBeFalse();

    $this->deleteJson(route('cortex.redirect-domains.destroy', $domain->id))->assertForbidden();
    mcpTool(DeleteRedirectDomainTool::class, ['id' => $domain->id])->assertHasErrors(['Unauthorized.']);

    expect(RedirectDomainModel::query()->whereKey($domain->id)->exists())->toBeTrue()
        ->and(OwnerPolicyFixture::$asked)->toBe(0);
});
