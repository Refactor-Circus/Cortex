<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;

/**
 * Cortex lays its screens out as Atrium lays out its own: `header.actions`
 * after the page's own actions, `before` and `after` around the content,
 * `aside` in a right-hand column only when it has visible sections,
 * `row.actions` in each row's last cell, and an open registered tab's panel
 * in place of the page content, with only `header.actions` kept.
 */
beforeEach(function (): void {
    Gate::define('viewAtrium', fn (mixed $user = null): bool => true);
    View::addNamespace('cortex-tests', dirname(__DIR__, 2).'/Fixtures/views');

    config()->set('cortex.tools', ['echo' => EchoTool::class]);

    VirtualAgentModel::factory()->published()->create(['name' => 'Helper', 'slug' => 'helper']);
});

function screenLayoutSection(string $target, string $slot, string $key, bool $visible = true): void
{
    Atrium::screens()->section($target, $slot, SectionDefinition::make($key)
        ->label($key)
        ->view('cortex-tests::screen-probe')
        ->resolve(fn (): array => ['probe' => $key])
        ->authorize(fn (): bool => $visible));
}

function screenLayoutTab(string $target): void
{
    Atrium::screens()->tab($target, TabDefinition::make('acme.tab')
        ->label('Acme tab')
        ->view('cortex-tests::screen-probe')
        ->resolve(fn (): array => ['probe' => 'acme.tab'])
        ->public());
}

function screenLayoutTestId(string $id): string
{
    return 'data-testid="'.$id.'"';
}

it('has no aside column while nothing is registered in aside', function (): void {
    $this->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertOk()
        ->assertDontSee(screenLayoutTestId('screen-aside'), false);
});

it('has no aside column while every aside section is hidden', function (): void {
    screenLayoutSection('cortex.virtual-agent', 'aside', 'acme.hidden', visible: false);

    $this->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertOk()
        ->assertDontSee(screenLayoutTestId('screen-aside'), false)
        ->assertDontSee(screenLayoutTestId('section-acme.hidden'), false);
});

it('renders a visible aside section in the aside column, after the content', function (): void {
    screenLayoutSection('cortex.virtual-agent', 'aside', 'acme.aside');

    $this->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertOk()
        ->assertSeeInOrder([
            screenLayoutTestId('save-agent'),
            screenLayoutTestId('screen-aside'),
            screenLayoutTestId('section-acme.aside'),
        ], false);
});

it('renders header actions after the page\'s own actions', function (): void {
    screenLayoutSection('cortex.virtual-agent', 'header.actions', 'acme.header');

    $this->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertOk()
        ->assertSeeInOrder([
            '<h1',
            screenLayoutTestId('back'),
            screenLayoutTestId('section-acme.header'),
            screenLayoutTestId('save-agent'),
        ], false);
});

it('renders header actions in the page header of a page without actions of its own', function (): void {
    screenLayoutSection('cortex.tools', 'header.actions', 'acme.header');

    $this->get(route('atrium.cortex.tools.index'))
        ->assertOk()
        ->assertSeeInOrder([
            '<h1',
            screenLayoutTestId('section-acme.header'),
            screenLayoutTestId('tool-search'),
        ], false);
});

it('renders before and after around the content', function (): void {
    screenLayoutSection('cortex.virtual-agent', 'before', 'acme.before');
    screenLayoutSection('cortex.virtual-agent', 'after', 'acme.after');

    $this->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertOk()
        ->assertSeeInOrder([
            screenLayoutTestId('section-acme.before'),
            screenLayoutTestId('save-agent'),
            screenLayoutTestId('section-acme.after'),
        ], false);
});

it('renders row actions in each row\'s last cell, after its own controls', function (): void {
    screenLayoutSection('cortex.virtual-agents', 'row.actions', 'acme.row');

    $html = (string) $this->get(route('atrium.cortex.virtual-agents.index'))
        ->assertOk()
        ->assertSeeInOrder([
            screenLayoutTestId('edit-helper'),
            screenLayoutTestId('delete-helper'),
            screenLayoutTestId('section-acme.row'),
            '</td>',
            '</tr>',
        ], false)
        ->getContent();

    $section = (int) strpos($html, screenLayoutTestId('section-acme.row'));
    $cell = (int) strrpos(substr($html, 0, $section), '<td');
    $rowEnd = (int) strpos($html, '</tr>', $section);

    // The section's cell holds the row's own controls, and no cell follows it.
    expect(substr($html, $cell, $section - $cell))->toContain(screenLayoutTestId('delete-helper'))
        ->and(substr($html, $section, $rowEnd - $section))->not->toContain('<td');
});

it('replaces the page content with an open tab, keeping only header actions', function (): void {
    screenLayoutTab('cortex.virtual-agent');
    screenLayoutSection('cortex.virtual-agent', 'header.actions', 'acme.header');
    screenLayoutSection('cortex.virtual-agent', 'before', 'acme.before');
    screenLayoutSection('cortex.virtual-agent', 'aside', 'acme.aside');
    screenLayoutSection('cortex.virtual-agent', 'after', 'acme.after');

    $this->get(route('atrium.cortex.virtual-agents.edit', ['helper', 'tab' => 'acme.tab']))
        ->assertOk()
        ->assertSee(screenLayoutTestId('section-acme.header'), false)
        ->assertSee(screenLayoutTestId('tab-panel-acme.tab'), false)
        ->assertSee('probe:acme.tab', false)
        ->assertDontSee(screenLayoutTestId('save-agent'), false)
        ->assertDontSee(screenLayoutTestId('versions-card'), false)
        ->assertDontSee(screenLayoutTestId('section-acme.before'), false)
        ->assertDontSee(screenLayoutTestId('screen-aside'), false)
        ->assertDontSee(screenLayoutTestId('section-acme.after'), false);
});

it('replaces a list with an open tab, rows and all', function (): void {
    screenLayoutTab('cortex.virtual-agents');
    screenLayoutSection('cortex.virtual-agents', 'header.actions', 'acme.header');
    screenLayoutSection('cortex.virtual-agents', 'row.actions', 'acme.row');

    $this->get(route('atrium.cortex.virtual-agents.index', ['tab' => 'acme.tab']))
        ->assertOk()
        ->assertSee(screenLayoutTestId('new-agent'), false)
        ->assertSee(screenLayoutTestId('section-acme.header'), false)
        ->assertSee(screenLayoutTestId('tab-panel-acme.tab'), false)
        ->assertDontSee(screenLayoutTestId('edit-helper'), false)
        ->assertDontSee(screenLayoutTestId('section-acme.row'), false);
});
