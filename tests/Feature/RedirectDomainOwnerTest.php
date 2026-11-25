<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\CreateRedirectDomainTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\ListRedirectDomainsTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use Workbench\App\Models\Organization;

/*
 * The owner of a redirect domain as Atrium's `cortex.mcp` tab and
 * `cortex.redirect-domains` section name it on another package's screen
 * (D-018): `owner_type` is the morph type the host stores the screen's record
 * as, and `owner_id` the record the screen passes, its route key. The
 * workbench organization stands in as a real host has it: no morph alias, a
 * ULID key and a slug route key, so a lookup by key alone fails here as it
 * did on a real host.
 */

beforeEach(function (): void {
    $this->organization = Organization::query()->create(['name' => 'Acme', 'slug' => 'w78504']);
    $this->actingAs(new GenericUser(['id' => 1]));
});

it('lists and adds an owner\'s domains by the route key its screen passes, storing its key', function (): void {
    $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'claude.ai', 'owner_type' => Organization::class, 'owner_id' => 'w78504'])
        ->assertCreated()
        ->assertJsonPath('data.owner_type', Organization::class)
        ->assertJsonPath('data.owner_id', $this->organization->getKey());

    $this->getJson(route('cortex.redirect-domains.index', ['owner_type' => Organization::class, 'owner_id' => 'w78504']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.domain', 'https://claude.ai');

    expect(RedirectDomainModel::query()->sole()->owner?->is($this->organization))->toBeTrue();
});

it('still takes the owner\'s key', function (): void {
    $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'claude.ai', 'owner_type' => Organization::class, 'owner_id' => $this->organization->getKey()])
        ->assertCreated();

    $this->getJson(route('cortex.redirect-domains.index', ['owner_type' => Organization::class, 'owner_id' => 'w78504']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('takes the route key over mcp too', function (): void {
    mcpTool(CreateRedirectDomainTool::class, ['domain' => 'claude.ai', 'owner_type' => Organization::class, 'owner_id' => 'w78504'])->assertOk();

    mcpTool(ListRedirectDomainsTool::class, ['owner_type' => Organization::class, 'owner_id' => 'w78504'])
        ->assertOk()
        ->assertSee('https://claude.ai');
});

it('finds no owner for the suite\'s name of a record or an unknown identifier', function (string $type, string $id): void {
    $this->getJson(route('cortex.redirect-domains.index', ['owner_type' => $type, 'owner_id' => $id]))->assertNotFound();
})->with([
    'a screen name, not a morph type' => ['roster.organization', 'w78504'],
    'an unknown route key' => [Organization::class, 'nobody'],
]);
