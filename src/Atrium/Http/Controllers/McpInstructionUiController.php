<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Cortex\Atrium\ScreenAccess;
use RefactorCircus\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\McpServer\Actions\DeleteMcpInstructionAction;
use RefactorCircus\Cortex\Domains\McpServer\Actions\PublishMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry;

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
        ]);
    }

    public function store(Request $request, string $server): RedirectResponse
    {
        $this->assertRegistered($server);

        $this->authorizeScreen('create', McpInstructionVersionModel::class, [ScreenAccess::serverInstruction($server, $this->override($server))]);

        $data = $request->validate(CreateMcpInstructionVersionAction::rules());

        app(CreateMcpInstructionVersionAction::class)->execute($server, $data);

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
}
