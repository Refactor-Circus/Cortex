<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\EchoCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\NoUpdateToolsMcpInstructionPolicy;

/*
 * The Atrium tool override editor on a concrete server's screen (AC 19), its
 * policy check (AC 9), the hidden editor on the `cortex` server (AC 12) and a
 * tool-only row on the screen (AC 15).
 *
 * Code tools of `grouped`, in code order: echo-mcp-tool, echo-request-tool,
 * renamed-echo; the registry adds echo-cortex-tool. The order tests read
 * them as a, b and c.
 */
beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    Gate::define('viewAtrium', fn (mixed $user = null): bool => true);
    app()->detectEnvironment(fn (): string => 'local');

    Cache::store('array')->flush();

    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
    app(ToolRegistry::class)->register('echo-cortex-tool', EchoCortexTool::class);
});

/**
 * Write the server's stored tool list directly, for values the update
 * action refuses (a stale entry), then start a new request.
 */
function writeEditorServerTools(string $server, mixed $tools): void
{
    McpInstructionModel::query()->updateOrCreate(['server' => $server], ['tools' => null]);

    DB::table(Tables::name('mcp_instructions'))->where('server', $server)->update([
        'tools' => $tools === null ? null : json_encode($tools, JSON_THROW_ON_ERROR),
    ]);

    $cache = app(PublicationCache::class);
    $cache->forget($cache->mcpToolsKey(), $cache->mcpInstructionsKey());

    app()->forgetScopedInstances();
}

/**
 * @return list<mixed>|null
 */
function storedEditorServerTools(string $server): ?array
{
    app()->forgetScopedInstances();

    /** @var list<mixed>|null */
    return McpInstructionModel::query()->where('server', $server)->first()?->tools;
}

/**
 * Every `tools[]` checkbox on the page, in page order: its value, whether it
 * is checked, and the text of its label.
 *
 * @return list<array{value: string, checked: bool, label: string}>
 */
function editorCheckboxes(TestResponse $response): array
{
    $html = (string) $response->getContent();

    preg_match_all('/<input\b[^>]*>/s', $html, $inputs);

    $boxes = [];

    foreach ($inputs[0] as $input) {
        if (! str_contains($input, 'name="tools[]"')) {
            continue;
        }

        preg_match('/\bid="([^"]*)"/', $input, $id);
        preg_match('/\bvalue="([^"]*)"/', $input, $value);
        preg_match('/<label for="'.preg_quote($id[1] ?? '', '/').'"[^>]*>(.*?)<\/label>/s', $html, $label);

        $boxes[] = [
            'value' => html_entity_decode($value[1] ?? ''),
            // The bare attribute, not the `checked:` utility in the class list.
            'checked' => (bool) preg_match('/\schecked(?=[\s\/>=])/', $input),
            'label' => trim(html_entity_decode($label[1] ?? '')),
        ];
    }

    return $boxes;
}

function editorTestId(string $id): string
{
    return 'data-testid="'.$id.'"';
}

describe('render', function (): void {
    it('lists the code tools in code order, then the registered tools, with no override checking the code tools', function (): void {
        $boxes = editorCheckboxes($this->get(route('atrium.cortex.servers.instructions', 'grouped'))->assertOk());

        expect(array_column($boxes, 'value'))->toBe(['echo-mcp-tool', 'echo-request-tool', 'renamed-echo', 'echo-cortex-tool'])
            ->and(array_column($boxes, 'checked'))->toBe([true, true, true, false])
            ->and(array_column($boxes, 'label'))->toBe(['echo-mcp-tool', 'echo-request-tool', 'renamed-echo', 'echo-cortex-tool']);
    });

    it('checks the stored entries and marks a stale one as unresolved, after the checklist', function (): void {
        writeEditorServerTools('grouped', ['gone-tool', 'renamed-echo', 'echo-cortex-tool']);

        $response = $this->get(route('atrium.cortex.servers.instructions', 'grouped'))->assertOk();
        $boxes = editorCheckboxes($response);

        expect(array_column($boxes, 'value'))->toBe(['echo-mcp-tool', 'echo-request-tool', 'renamed-echo', 'echo-cortex-tool', 'gone-tool'])
            ->and(array_column($boxes, 'checked'))->toBe([false, false, true, true, true])
            ->and($boxes[4]['label'])->toEndWith('(unresolved)')
            ->and($boxes[4]['label'])->toStartWith('gone-tool')
            ->and(array_filter(array_slice(array_column($boxes, 'label'), 0, 4), fn (string $label): bool => str_contains($label, '(unresolved)')))->toBe([]);

        $response->assertSee(editorTestId('restore-server-tools'), false)
            ->assertSee('name="restore"', false)
            ->assertSee('name="save"', false)
            ->assertSee('name="confirm_empty"', false);
    });

    it('offers no restore button while the server serves its code tools', function (): void {
        $this->get(route('atrium.cortex.servers.instructions', 'grouped'))
            ->assertOk()
            ->assertSee('name="save"', false)
            ->assertDontSee('name="restore"', false);
    });

    it('renders no tool editor and no restore button for the cortex server', function (): void {
        writeEditorServerTools('cortex', ['list-tools-tool']);

        $this->get(route('atrium.cortex.servers.instructions', 'cortex'))
            ->assertOk()
            ->assertDontSee('name="tools[]"', false)
            ->assertDontSee('name="restore"', false)
            ->assertDontSee('name="confirm_empty"', false)
            ->assertDontSee(editorTestId('server-tools-form'), false);
    });

    it('renders a tool-only row with the instructions declared in code', function (): void {
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool'], 'save' => '1'])
            ->assertRedirect(route('atrium.cortex.servers.instructions', 'grouped'));

        $instruction = McpInstructionModel::query()->where('server', 'grouped')->sole();

        expect($instruction->published_version_id)->toBeNull()
            ->and($instruction->versions()->count())->toBe(0);

        app()->forgetScopedInstances();

        $response = $this->get(route('atrium.cortex.servers.instructions', 'grouped'))
            ->assertOk()
            ->assertSee('Grouped tools server instructions.')
            ->assertSee('data-status="from_code"', false)
            ->assertSee('data-status="overridden"', false);

        expect(array_column(array_filter(editorCheckboxes($response), fn (array $box): bool => $box['checked']), 'value'))->toBe(['echo-mcp-tool']);
    });
});

describe('save and restore', function (): void {
    it('stores the ticked tools and restores the code tools', function (): void {
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-request-tool', 'echo-cortex-tool'], 'save' => '1'])
            ->assertRedirect(route('atrium.cortex.servers.instructions', 'grouped'))
            ->assertSessionHasNoErrors();

        expect(storedEditorServerTools('grouped'))->toBe(['echo-request-tool', 'echo-cortex-tool']);

        $this->delete(route('atrium.cortex.servers.tools.destroy', 'grouped'), ['restore' => '1'])
            ->assertRedirect(route('atrium.cortex.servers.instructions', 'grouped'))
            ->assertSessionHasNoErrors();

        // A tool-only row goes with its tools.
        expect(McpInstructionModel::query()->where('server', 'grouped')->exists())->toBeFalse();
    });

    it('restores the code tools and keeps the published instructions', function (): void {
        $this->post(route('atrium.cortex.servers.instructions.store', 'grouped'), ['content' => 'Kept.', 'publish' => true])->assertRedirect();
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool'], 'save' => '1'])->assertRedirect();

        $this->delete(route('atrium.cortex.servers.tools.destroy', 'grouped'), ['restore' => '1'])->assertRedirect();

        $instruction = McpInstructionModel::query()->where('server', 'grouped')->sole();

        expect($instruction->tools)->toBeNull()
            ->and($instruction->publishedVersion?->content)->toBe('Kept.');
    });

    it('refuses an empty selection without confirmation, keeping the stored list', function (): void {
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool'], 'save' => '1'])->assertRedirect();

        $this->from(route('atrium.cortex.servers.instructions', 'grouped'))
            ->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['save' => '1'])
            ->assertRedirect(route('atrium.cortex.servers.instructions', 'grouped'))
            ->assertSessionHasErrors('confirm_empty');

        expect(storedEditorServerTools('grouped'))->toBe(['echo-mcp-tool']);
    });

    it('refuses an empty selection without confirmation on a server without an override', function (): void {
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['save' => '1'])
            ->assertSessionHasErrors('confirm_empty');

        expect(McpInstructionModel::query()->count())->toBe(0);
    });

    it('stores an empty list with confirmation', function (): void {
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['confirm_empty' => '1', 'save' => '1'])
            ->assertRedirect(route('atrium.cortex.servers.instructions', 'grouped'))
            ->assertSessionHasNoErrors();

        expect(storedEditorServerTools('grouped'))->toBe([]);
    });

    it('keeps the stored order for entries already saved and the checklist order for the rest', function (): void {
        // a = echo-mcp-tool, b = echo-request-tool, c = renamed-echo.
        writeEditorServerTools('grouped', ['echo-request-tool', 'echo-mcp-tool']);

        // Tick a, b and c: the form posts them in checklist order.
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), [
            'tools' => ['echo-mcp-tool', 'echo-request-tool', 'renamed-echo'],
            'save' => '1',
        ])->assertSessionHasNoErrors();

        expect(storedEditorServerTools('grouped'))->toBe(['echo-request-tool', 'echo-mcp-tool', 'renamed-echo']);

        writeEditorServerTools('grouped', ['echo-request-tool', 'echo-mcp-tool']);

        // Tick b and c only, unticking a.
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), [
            'tools' => ['echo-request-tool', 'renamed-echo'],
            'save' => '1',
        ])->assertSessionHasNoErrors();

        expect(storedEditorServerTools('grouped'))->toBe(['echo-request-tool', 'renamed-echo']);
    });

    it('refuses a ticked entry the server cannot serve, keeping the stored list', function (): void {
        writeEditorServerTools('grouped', ['gone-tool', 'echo-mcp-tool']);

        // Saved in stored order, so the stale entry is the first one validated.
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool', 'gone-tool'], 'save' => '1'])
            ->assertSessionHasErrors('tools.0');

        expect(storedEditorServerTools('grouped'))->toBe(['gone-tool', 'echo-mcp-tool']);
    });

    it('refuses the cortex server', function (): void {
        $this->put(route('atrium.cortex.servers.tools.update', 'cortex'), ['tools' => [], 'confirm_empty' => '1', 'save' => '1'])
            ->assertSessionHasErrors('server');

        $this->delete(route('atrium.cortex.servers.tools.destroy', 'cortex'), ['restore' => '1'])
            ->assertSessionHasErrors('server');

        expect(McpInstructionModel::query()->count())->toBe(0);
    });

    it('answers 404 for a server that is not registered', function (): void {
        $this->put(route('atrium.cortex.servers.tools.update', 'missing'), ['tools' => ['echo-mcp-tool'], 'save' => '1'])->assertNotFound();
        $this->delete(route('atrium.cortex.servers.tools.destroy', 'missing'), ['restore' => '1'])->assertNotFound();
    });
});

describe('authorization (AC 9)', function (): void {
    it('blocks a tool addition and a restore under a policy that denies updateTools, keeping the stored list', function (): void {
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool'], 'save' => '1'])->assertRedirect();

        Gate::policy(McpInstructionModel::class, NoUpdateToolsMcpInstructionPolicy::class);

        $this->actingAs(new GenericUser(['id' => 1, 'name' => 'Viewer']));

        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['tools' => ['echo-mcp-tool', 'echo-cortex-tool'], 'save' => '1'])
            ->assertForbidden();
        $this->delete(route('atrium.cortex.servers.tools.destroy', 'grouped'), ['restore' => '1'])
            ->assertForbidden();

        expect(storedEditorServerTools('grouped'))->toBe(['echo-mcp-tool']);
    });

    it('shows the served tools read-only without the editor to someone who may not update them', function (): void {
        $this->put(route('atrium.cortex.servers.tools.update', 'grouped'), ['tools' => ['renamed-echo'], 'save' => '1'])->assertRedirect();

        Gate::policy(McpInstructionModel::class, NoUpdateToolsMcpInstructionPolicy::class);
        app()->forgetScopedInstances();

        $this->actingAs(new GenericUser(['id' => 1, 'name' => 'Viewer']))
            ->get(route('atrium.cortex.servers.instructions', 'grouped'))
            ->assertOk()
            ->assertSee('renamed-echo')
            ->assertDontSee('name="tools[]"', false)
            ->assertDontSee('name="save"', false)
            ->assertDontSee('name="restore"', false);
    });
});
