<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Cortex\Atrium\ScreenAccess;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ClearConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\DeleteMcpInstructionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\PublishMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\ClearConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerToolResolver;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;

final class McpInstructionUiController
{
    use AuthorizesScreens;

    public function show(string $server): View
    {
        $this->assertRegistered($server);

        $instruction = $this->override($server);

        $this->authorizeScreen('view', ScreenAccess::serverInstruction($server, $instruction));

        /** @var view-string $view */
        $view = 'cortex::ui.servers.instructions';

        return view($view, [
            'server' => $server,
            'codeInstructions' => app(McpServerRegistry::class)->defaultInstructions($server),
            'instruction' => $instruction,
            'subject' => ScreenAccess::serverInstruction($server, $instruction),
            'versions' => $instruction?->versions()->chaperone('mcpInstruction')->orderByDesc('version')->get() ?? collect(),
            'toolEditor' => $this->toolEditor($server, $instruction),
        ]);
    }

    public function store(Request $request, string $server): RedirectResponse
    {
        $this->assertRegistered($server);

        $this->authorizeScreen('create', McpInstructionVersionModel::class, [ScreenAccess::serverInstruction($server, $this->override($server))]);

        app(CreateMcpInstructionVersionAction::class)->execute($server, CreateMcpInstructionVersionData::from($request->all()));

        return redirect()
            ->route('atrium.cortex.servers.instructions', $server)
            ->with('status', __('cortex::cortex.version_created'));
    }

    public function publish(string $server, int $version): RedirectResponse
    {
        $this->assertRegistered($server);

        $instruction = $this->override($server);

        abort_if($instruction === null, 404);

        $this->authorizeScreen('publish', $instruction->versions()->where('version', $version)->firstOrFail());

        app(PublishMcpInstructionVersionAction::class)->execute($instruction, $version);

        return redirect()
            ->route('atrium.cortex.servers.instructions', $server)
            ->with('status', __('cortex::cortex.version_published'));
    }

    public function destroy(string $server): RedirectResponse
    {
        $this->assertRegistered($server);

        $instruction = $this->override($server);

        abort_if($instruction === null, 404);

        $this->authorizeScreen('delete', $instruction);

        app(DeleteMcpInstructionAction::class)->execute($instruction);

        return redirect()
            ->route('atrium.cortex.servers.instructions', $server)
            ->with('status', __('cortex::cortex.override_removed'));
    }

    /**
     * Save the ticked tools as the server's tool override. Entries already
     * stored keep their stored order and newly ticked ones follow in
     * checklist order (the order the form posts them in). Saving with
     * nothing ticked serves no tools at all, so it needs `confirm_empty`.
     */
    public function updateTools(Request $request, string $server): RedirectResponse
    {
        $this->assertRegistered($server);

        $instruction = $this->override($server);

        $this->authorizeScreen('updateTools', ScreenAccess::serverInstruction($server, $instruction));

        $selected = $request->input('tools', []);

        if (($selected === [] || $selected === null) && ! $request->boolean('confirm_empty')) {
            throw ValidationException::withMessages([
                'confirm_empty' => __('cortex::cortex.empty_tools_unconfirmed'),
            ]);
        }

        $data = UpdateConcreteServerToolsData::from(
            array_merge($request->all(), ['tools' => $this->savedOrder($instruction?->tools, $selected ?? [])]),
            $server,
        );

        app(UpdateConcreteServerToolsAction::class)->execute($server, $data);

        return redirect()
            ->route('atrium.cortex.servers.instructions', $server)
            ->with('status', __('cortex::cortex.tools_updated'));
    }

    /**
     * Clear the server's tool override, so it serves its code tools again.
     */
    public function clearTools(Request $request, string $server): RedirectResponse
    {
        $this->assertRegistered($server);

        $this->authorizeScreen('updateTools', ScreenAccess::serverInstruction($server, $this->override($server)));

        app(ClearConcreteServerToolsAction::class)->execute($server, ClearConcreteServerToolsData::from($request->all(), $server));

        return redirect()
            ->route('atrium.cortex.servers.instructions', $server)
            ->with('status', __('cortex::cortex.code_tools_restored'));
    }

    /**
     * The override row, or null when the server still uses the instructions it
     * declares in code.
     */
    private function override(string $server): ?McpInstructionModel
    {
        return McpInstructionModel::query()
            ->where('server', $server)
            ->with('publishedVersion')
            ->first();
    }

    private function assertRegistered(string $server): void
    {
        abort_unless(app(McpServerRegistry::class)->has($server), 404);
    }

    /**
     * What the tool editor shows, or null for a server whose tools cannot be
     * overridden (the `cortex` server), which gets no editor at all.
     *
     * The checklist holds the server's code tools in code order, then the
     * registered Cortex tools in registry order, then the stored entries that
     * no longer resolve, each marked as unresolved. An item is checked when
     * it is in the stored list, or, with no tool override, when the server
     * declares it in code (what it serves now). `served` is what the server
     * serves, for a viewer who may not change it.
     *
     * @return array{overridden: bool, checked: list<string>, served: list<string>, items: list<array{name: string, label: string, tags: list<string>}>}|null
     */
    private function toolEditor(string $server, ?McpInstructionModel $instruction): ?array
    {
        $servers = app(McpServerRegistry::class);

        if (! $servers->supportsToolOverrides($server)) {
            return null;
        }

        $stored = $instruction?->tools;
        $stored = is_array($stored) ? array_values(array_filter($stored, is_string(...))) : null;

        $report = app(ServerToolResolver::class)->report($server, $stored);
        $code = $servers->defaultTools($server);
        $registry = app(ToolRegistry::class);

        $items = [];

        foreach ([...$code, ...$registry->names()] as $name) {
            $items[$name] ??= [
                'name' => $name,
                'label' => $name,
                'tags' => $registry->has($name) ? $registry->tagsFor($name) : [],
            ];
        }

        foreach ($report['missing_tools'] as $name) {
            $items[$name] = [
                'name' => $name,
                'label' => $name.' ('.__('cortex::cortex.unresolved').')',
                'tags' => $items[$name]['tags'] ?? [],
            ];
        }

        return [
            'overridden' => $report['tools_overridden'],
            'checked' => $stored ?? $code,
            'served' => $report['tools'],
            'items' => array_values($items),
        ];
    }

    /**
     * The ticked tools in saved order: the stored entries still ticked, in
     * stored order, then the newly ticked ones in the order posted. Input
     * that is not a list is passed on as is, for validation to refuse.
     */
    private function savedOrder(mixed $stored, mixed $selected): mixed
    {
        if (! is_array($selected)) {
            return $selected;
        }

        $selected = array_values($selected);
        $stored = is_array($stored) ? array_values($stored) : [];

        return [
            ...array_values(array_filter($stored, fn (mixed $entry): bool => in_array($entry, $selected, true))),
            ...array_values(array_filter($selected, fn (mixed $entry): bool => ! in_array($entry, $stored, true))),
        ];
    }
}
