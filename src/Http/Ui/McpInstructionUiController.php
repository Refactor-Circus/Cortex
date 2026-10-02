<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Cortex\Actions\CreateMcpInstructionVersionAction;
use JayI\Cortex\Actions\DeleteMcpInstructionAction;
use JayI\Cortex\Actions\PublishMcpInstructionVersionAction;
use JayI\Cortex\Http\Ui\Concerns\AuthorizesScreens;
use JayI\Cortex\Mcp\McpServerRegistry;
use JayI\Cortex\Models\McpInstruction;
use JayI\Cortex\Models\McpInstructionVersion;

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

        $this->authorizeScreen('create', McpInstructionVersion::class, [ScreenAccess::serverInstruction($server, $this->override($server))]);

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
    private function override(string $server): ?McpInstruction
    {
        return McpInstruction::query()
            ->where('server', $server)
            ->with('publishedVersion')
            ->first();
    }

    private function assertRegistered(string $server): void
    {
        abort_unless(app(McpServerRegistry::class)->has($server), 404);
    }
}
