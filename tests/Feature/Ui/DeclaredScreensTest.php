<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Cortex\Atrium\Screens;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Support\DbAgent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;

/**
 * Every Cortex page with a controller view declares itself as a standard
 * Atrium screen, so other packages can add a tab and a section in each of its
 * slots without Cortex knowing them.
 */
beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    Gate::define('viewAtrium', fn (mixed $user = null): bool => true);
    View::addNamespace('cortex-tests', dirname(__DIR__, 2).'/Fixtures/views');

    config()->set('cortex.tools', ['echo' => EchoTool::class]);
    config()->set('cortex.mcp.servers', ['echo-server' => EchoServer::class]);
    config()->set('mcp.redirect_domains', []);

    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
});

/**
 * Each screen: its key, its kind, the slot only its kind has, the slot it
 * must not render (the other kind's), and its URL with a query, after
 * creating at least one row on a list screen.
 *
 * @return array<string, array{string, string, string, string, Closure(array<string, string>): string}>
 */
function declaredScreens(): array
{
    return [
        'cortex.virtual-agents' => ['cortex.virtual-agents', Screen::LIST, 'row.actions', 'aside', function (array $query): string {
            declaredScreenAgent();

            return route('atrium.cortex.virtual-agents.index', $query);
        }],
        'cortex.virtual-agent-create' => ['cortex.virtual-agent-create', Screen::DETAIL, 'aside', 'row.actions', fn (array $query): string => route('atrium.cortex.virtual-agents.create', $query)],
        'cortex.virtual-agent' => ['cortex.virtual-agent', Screen::DETAIL, 'aside', 'row.actions', function (array $query): string {
            declaredScreenAgent();

            return route('atrium.cortex.virtual-agents.edit', ['helper', ...$query]);
        }],
        'cortex.concrete-agents' => ['cortex.concrete-agents', Screen::LIST, 'row.actions', 'aside', fn (array $query): string => route('atrium.cortex.concrete-agents.index', $query)],
        'cortex.concrete-agent' => ['cortex.concrete-agent', Screen::DETAIL, 'aside', 'row.actions', fn (array $query): string => route('atrium.cortex.concrete-agents.show', ['echo-agent', ...$query])],
        'cortex.run' => ['cortex.run', Screen::DETAIL, 'aside', 'row.actions', fn (array $query): string => route('atrium.cortex.run', $query)],
        'cortex.tools' => ['cortex.tools', Screen::LIST, 'row.actions', 'aside', fn (array $query): string => route('atrium.cortex.tools.index', $query)],
        'cortex.tool-description' => ['cortex.tool-description', Screen::DETAIL, 'aside', 'row.actions', fn (array $query): string => route('atrium.cortex.tools.description', ['echo', ...$query])],
        'cortex.servers' => ['cortex.servers', Screen::LIST, 'row.actions', 'aside', fn (array $query): string => route('atrium.cortex.servers.index', $query)],
        'cortex.server-instructions' => ['cortex.server-instructions', Screen::DETAIL, 'aside', 'row.actions', fn (array $query): string => route('atrium.cortex.servers.instructions', ['echo-server', ...$query])],
        'cortex.redirect-domains' => ['cortex.redirect-domains', Screen::LIST, 'row.actions', 'aside', function (array $query): string {
            app(CreateRedirectDomainAction::class)->execute(new CreateRedirectDomainData(domain: 'claude.ai'));

            return route('atrium.cortex.redirect-domains.index', $query);
        }],
    ];
}

function declaredScreenAgent(): VirtualAgentModel
{
    return VirtualAgentModel::factory()->published()->create(['name' => 'Helper', 'slug' => 'helper']);
}

/**
 * The key of the section a slot gets in these tests.
 */
function declaredScreenSection(string $slot): string
{
    return 'acme.'.str_replace('.', '-', $slot);
}

function declaredScreenTestId(string $id): string
{
    return 'data-testid="'.$id.'"';
}

/**
 * A public tab `acme.tab`, and a public section in every standard slot and in
 * `history`, each rendering its own key.
 */
function declaredScreenRegister(string $key): void
{
    Atrium::screens()->tab($key, TabDefinition::make('acme.tab')
        ->label('Acme tab')
        ->view('cortex-tests::screen-probe')
        ->resolve(fn (): array => ['probe' => 'acme.tab'])
        ->public());

    foreach (['header.actions', 'before', 'after', 'aside', 'row.actions', 'history'] as $slot) {
        $section = declaredScreenSection($slot);

        Atrium::screens()->section($key, $slot, SectionDefinition::make($section)
            ->label($section)
            ->view('cortex-tests::screen-probe')
            ->resolve(fn (): array => ['probe' => $section])
            ->public());
    }
}

it('declares a standard screen with only the overview tab, and no history slot', function (string $kind, array $slots, array $rowSlots): void {
    $screen = Screens::declare('cortex.example', $kind, null, VirtualAgentModel::class);

    expect($screen->key)->toBe('cortex.example')
        ->and($screen->kind)->toBe($kind)
        ->and($screen->slots)->toBe($slots)
        ->and($screen->rowSlots)->toBe($rowSlots)
        ->and(array_map(fn (TabDefinition $tab): string => $tab->key, $screen->tabs))->toBe([Screen::OVERVIEW])
        ->and($screen->defaultTab)->toBe(Screen::OVERVIEW)
        ->and([...$screen->slots, ...$screen->rowSlots])->not->toContain('history');
})->with([
    'detail' => [Screen::DETAIL, ['header.actions', 'before', 'after', 'aside'], []],
    'list' => [Screen::LIST, ['header.actions', 'before', 'after'], ['row.actions']],
]);

it('renders a registered tab and a section in each of its standard slots', function (string $key, string $kind, string $own, string $other, Closure $url): void {
    declaredScreenRegister($key);

    $this->get($url([]))
        ->assertOk()
        ->assertSee(declaredScreenTestId('tab-acme.tab'), false)
        ->assertSee(declaredScreenTestId('section-'.declaredScreenSection('header.actions')), false)
        ->assertSee(declaredScreenTestId('section-'.declaredScreenSection('before')), false)
        ->assertSee(declaredScreenTestId('section-'.declaredScreenSection('after')), false)
        ->assertSee(declaredScreenTestId('section-'.declaredScreenSection($own)), false)
        ->assertSee('probe:'.declaredScreenSection($own), false)
        // The other kind's slot never renders here, and there is no history slot.
        ->assertDontSee(declaredScreenTestId('section-'.declaredScreenSection($other)), false)
        ->assertDontSee(declaredScreenTestId('section-'.declaredScreenSection('history')), false);
})->with(declaredScreens());

it('opens the registered tab through ?tab=', function (string $key, string $kind, string $own, string $other, Closure $url): void {
    declaredScreenRegister($key);

    $this->get($url(['tab' => 'acme.tab']))
        ->assertOk()
        ->assertSee(declaredScreenTestId('tab-panel-acme.tab'), false)
        ->assertSee('probe:acme.tab', false)
        ->assertSee(declaredScreenTestId('section-'.declaredScreenSection('header.actions')), false)
        ->assertDontSee(declaredScreenTestId('section-'.declaredScreenSection('before')), false)
        ->assertDontSee(declaredScreenTestId('section-'.declaredScreenSection($own)), false)
        ->assertDontSee(declaredScreenTestId('section-'.declaredScreenSection('after')), false);
})->with(declaredScreens());

it('renders no extension markup while nothing is registered', function (string $key, string $kind, string $own, string $other, Closure $url): void {
    $this->get($url([]))
        ->assertOk()
        ->assertDontSee(declaredScreenTestId('screen-tabs'), false)
        ->assertDontSee(declaredScreenTestId('screen-aside'), false)
        ->assertDontSee('data-testid="section-', false)
        ->assertDontSee('data-testid="tab-panel-', false);
})->with(declaredScreens());

it('declares the run page when it shows a result', function (): void {
    DbAgent::fake(['Hi from the agent.']);
    declaredScreenAgent();
    declaredScreenRegister('cortex.run');

    $this->post(route('atrium.cortex.run.store'), ['agent' => 'virtual:helper', 'input' => 'Hi'])
        ->assertOk()
        ->assertSee('Hi from the agent.')
        ->assertSee(declaredScreenTestId('section-'.declaredScreenSection('before')), false)
        ->assertSee(declaredScreenTestId('section-'.declaredScreenSection('aside')), false);
});

it('gives a configured redirect domain no row section', function (): void {
    config()->set('mcp.redirect_domains', ['https://configured.test']);
    app(CreateRedirectDomainAction::class)->execute(new CreateRedirectDomainData(domain: 'claude.ai'));
    declaredScreenRegister('cortex.redirect-domains');

    $html = $this->get(route('atrium.cortex.redirect-domains.index'))
        ->assertOk()
        ->assertSee('https://configured.test')
        ->getContent();

    $rowSection = declaredScreenTestId('section-'.declaredScreenSection('row.actions'));
    $configured = strpos((string) $html, declaredScreenTestId('configured-redirect-domains'));
    $stored = strpos((string) $html, declaredScreenTestId('stored-redirect-domains'));

    expect($configured)->toBeInt()
        ->and($stored)->toBeInt()
        ->and(substr_count((string) $html, $rowSection))->toBe(1)
        ->and(substr((string) $html, (int) $configured, (int) $stored - (int) $configured))->not->toContain($rowSection)
        ->and(substr((string) $html, (int) $stored))->toContain($rowSection);
});
