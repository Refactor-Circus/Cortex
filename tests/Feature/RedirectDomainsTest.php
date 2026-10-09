<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\DeleteRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainCreatedActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainDeletedActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\CreateRedirectDomainTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\DeleteRedirectDomainTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools\ListRedirectDomainsTool;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use Laravel\Mcp\Facades\Mcp;

/**
 * Register an MCP client the way a client does, returning the response.
 */
function registerClient(string $redirectUri): TestResponse
{
    return test()->postJson('/oauth/register', [
        'client_name' => 'Client',
        'redirect_uris' => [$redirectUri],
    ]);
}

/**
 * Whether laravel/mcp accepted the redirect URI. Passport is not installed
 * here, so an accepted registration stops at the server error after
 * validation.
 */
function redirectAccepted(TestResponse $response): bool
{
    return $response->json('error') !== 'invalid_redirect_uri';
}

beforeEach(function (): void {
    Mcp::oauthRoutes();
    config(['mcp.redirect_domains' => []]);
});

it('normalizes hosts, origins and redirect urls to origins', function (string $input, ?string $origin): void {
    expect(RedirectDomains::normalize($input))->toBe($origin);
})->with([
    ['claude.ai', 'https://claude.ai'],
    ['https://Claude.AI/', 'https://claude.ai'],
    ['https://claude.ai/api/mcp/auth_callback', 'https://claude.ai'],
    ['http://localhost:6274/oauth/callback', 'http://localhost:6274'],
    ['*', null],
    ['', null],
    ['ftp://example.com', null],
    ['https://user:pass@example.com', null],
    ['https://exa mple.com', null],
]);

it('rejects redirect uris on domains that are neither configured nor stored', function (): void {
    expect(redirectAccepted(registerClient('https://claude.ai/api/mcp/auth_callback')))->toBeFalse();
});

it('accepts redirect uris on stored domains', function (): void {
    app(CreateRedirectDomainAction::class)->execute(['domain' => 'claude.ai']);

    expect(redirectAccepted(registerClient('https://claude.ai/api/mcp/auth_callback')))->toBeTrue()
        ->and(redirectAccepted(registerClient('https://claude.ai.evil.test/callback')))->toBeFalse();
});

it('accepts domains owned by anyone', function (): void {
    $owner = VirtualAgentModel::factory()->create();

    app(CreateRedirectDomainAction::class)->execute(['domain' => 'https://chatgpt.com'], $owner);

    expect(redirectAccepted(registerClient('https://chatgpt.com/connector_platform_oauth_redirect')))->toBeTrue();
});

it('keeps the configured domains and leaves the config as it was', function (): void {
    config(['mcp.redirect_domains' => ['https://cursor.com']]);
    app(CreateRedirectDomainAction::class)->execute(['domain' => 'claude.ai']);

    expect(redirectAccepted(registerClient('https://cursor.com/callback')))->toBeTrue()
        ->and(redirectAccepted(registerClient('https://claude.ai/callback')))->toBeTrue()
        ->and(config('mcp.redirect_domains'))->toBe(['https://cursor.com']);
});

it('stops accepting a domain once removed', function (): void {
    $domain = app(CreateRedirectDomainAction::class)->execute(['domain' => 'claude.ai']);
    expect(redirectAccepted(registerClient('https://claude.ai/callback')))->toBeTrue();

    app(DeleteRedirectDomainAction::class)->execute($domain);

    expect(redirectAccepted(registerClient('https://claude.ai/callback')))->toBeFalse();
});

it('ignores stored domains when switched off', function (): void {
    config(['cortex.redirect_domains.enabled' => false]);
    app(CreateRedirectDomainAction::class)->execute(['domain' => 'claude.ai']);

    expect(redirectAccepted(registerClient('https://claude.ai/callback')))->toBeFalse();
});

it('adds a domain once per owner and fires its action events', function (): void {
    Event::fake([RedirectDomainCreatedActionEvent::class, RedirectDomainDeletedActionEvent::class]);
    $owner = VirtualAgentModel::factory()->create();

    $first = app(CreateRedirectDomainAction::class)->execute(['domain' => 'claude.ai'], $owner);
    $again = app(CreateRedirectDomainAction::class)->execute(['domain' => 'https://claude.ai/'], $owner);
    $global = app(CreateRedirectDomainAction::class)->execute(['domain' => 'claude.ai']);

    expect($again->id)->toBe($first->id)
        ->and($global->id)->not->toBe($first->id)
        ->and($first->owner_type)->toBe($owner->getMorphClass())
        ->and($first->owner_id)->toBe((string) $owner->getKey())
        ->and(app(RedirectDomains::class)->stored())->toBe(['https://claude.ai']);

    app(DeleteRedirectDomainAction::class)->execute($global);

    Event::assertDispatchedTimes(RedirectDomainCreatedActionEvent::class, 3);
    Event::assertDispatched(RedirectDomainDeletedActionEvent::class);
});

it('manages domains over the json api', function (): void {
    $owner = VirtualAgentModel::factory()->create();

    $id = $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'https://claude.ai/callback'])
        ->assertCreated()
        ->assertJsonPath('data.domain', 'https://claude.ai')
        ->assertJsonPath('data.owner_type', null)
        ->json('data.id');

    $this->postJson(route('cortex.redirect-domains.store'), [
        'domain' => 'chatgpt.com',
        'owner_type' => $owner->getMorphClass(),
        'owner_id' => (string) $owner->getKey(),
    ])->assertCreated()->assertJsonPath('data.owner_id', (string) $owner->getKey());

    $this->getJson(route('cortex.redirect-domains.index'))->assertOk()->assertJsonCount(2, 'data');
    $this->getJson(route('cortex.redirect-domains.index', ['owner_type' => $owner->getMorphClass(), 'owner_id' => $owner->getKey()]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.domain', 'https://chatgpt.com');

    $this->deleteJson(route('cortex.redirect-domains.destroy', $id))->assertNoContent();

    expect(RedirectDomainModel::query()->count())->toBe(1);
});

it('refuses invalid domains and unknown owners over the json api', function (): void {
    $this->postJson(route('cortex.redirect-domains.store'), ['domain' => '*'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('domain');

    $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'claude.ai', 'owner_type' => 'Nope\\Missing', 'owner_id' => '1'])
        ->assertNotFound();

    $this->postJson(route('cortex.redirect-domains.store'), ['domain' => 'claude.ai', 'owner_type' => VirtualAgentModel::class, 'owner_id' => 'missing'])
        ->assertNotFound();
});

it('lists domains over mcp with parity to the http payload', function (): void {
    mcpTool(ListRedirectDomainsTool::class)->assertOk()->assertStructuredContent(['data' => []]);

    mcpTool(CreateRedirectDomainTool::class, ['domain' => 'claude.ai'])->assertOk();

    $http = $this->getJson(route('cortex.redirect-domains.index'))->json('data');

    mcpTool(ListRedirectDomainsTool::class)->assertOk()->assertStructuredContent(['data' => $http]);
});

it('creates and deletes domains over mcp with parity to the http payload', function (): void {
    $owner = VirtualAgentModel::factory()->create();

    $response = mcpTool(CreateRedirectDomainTool::class, [
        'domain' => 'https://claude.ai',
        'owner_type' => $owner->getMorphClass(),
        'owner_id' => (string) $owner->getKey(),
    ])->assertOk();

    $http = $this->getJson(route('cortex.redirect-domains.index'))->json('data.0');
    $response->assertStructuredContent($http);

    mcpTool(DeleteRedirectDomainTool::class, ['id' => $http['id']])->assertOk();

    expect(RedirectDomainModel::query()->count())->toBe(0);
});

it('manages domains from the dashboard', function (): void {
    ValidateCsrfToken::except(['*']);
    app()->detectEnvironment(fn (): string => 'local');
    config(['mcp.redirect_domains' => ['https://cursor.com']]);
    $owner = VirtualAgentModel::factory()->create(['name' => 'Acme agent']);
    app(CreateRedirectDomainAction::class)->execute(['domain' => 'chatgpt.com'], $owner);

    app(CreateRedirectDomainAction::class)->execute(['domain' => 'perplexity.ai']);

    $this->get(route('atrium.cortex.redirect-domains.index'))
        ->assertOk()
        ->assertSeeInOrder(['https://chatgpt.com', 'https://cursor.com', 'https://perplexity.ai'])
        ->assertSee('Acme agent')
        ->assertSee('data-testid="redirect-domain-config"', false)
        ->assertSee('data-testid="redirect-domain-global"', false)
        ->assertSee('data-testid="redirect-domain-owned"', false);

    $this->get(route('atrium.cortex.redirect-domains.index', ['source' => 'config']))
        ->assertOk()
        ->assertSee('https://cursor.com')
        ->assertDontSee('https://chatgpt.com')
        ->assertDontSee('https://perplexity.ai');

    $this->get(route('atrium.cortex.redirect-domains.index', ['source' => 'global']))
        ->assertSee('https://perplexity.ai')
        ->assertDontSee('https://cursor.com')
        ->assertDontSee('https://chatgpt.com');

    $this->get(route('atrium.cortex.redirect-domains.index', ['source' => 'owned']))
        ->assertSee('https://chatgpt.com')
        ->assertDontSee('https://perplexity.ai');

    $this->post(route('atrium.cortex.redirect-domains.store'), ['domain' => 'claude.ai'])
        ->assertRedirect(route('atrium.cortex.redirect-domains.index'));

    $domain = RedirectDomainModel::query()->where('domain', 'https://claude.ai')->sole();
    expect($domain->owner_type)->toBeNull();

    $this->delete(route('atrium.cortex.redirect-domains.destroy', $domain->id))
        ->assertRedirect(route('atrium.cortex.redirect-domains.index'));

    expect(RedirectDomainModel::query()->where('domain', 'https://claude.ai')->exists())->toBeFalse();
});
