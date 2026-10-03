<?php

declare(strict_types=1);

namespace JayI\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use JayI\Cortex\Atrium\ScreenAccess;
use JayI\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction;
use JayI\Cortex\Domains\Tool\Actions\DeleteToolDescriptionAction;
use JayI\Cortex\Domains\Tool\Actions\PublishToolDescriptionVersionAction;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;

final class ToolDescriptionUiController
{
    use AuthorizesScreens;

    public function show(string $tool): View
    {
        $this->assertRegistered($tool);

        $description = $this->override($tool);

        $this->authorizeScreen('view', ScreenAccess::toolDescription($tool, $description));

        /** @var view-string $view */
        $view = 'cortex::ui.tools.description';

        return view($view, [
            'tool' => $tool,
            'codeDescription' => app(ToolRegistry::class)->get($tool)->description(),
            'description' => $description,
            'subject' => ScreenAccess::toolDescription($tool, $description),
            'versions' => $description?->versions()->chaperone('toolDescription')->orderByDesc('version')->get() ?? collect(),
        ]);
    }

    public function store(Request $request, string $tool): RedirectResponse
    {
        $this->assertRegistered($tool);

        $this->authorizeScreen('create', ToolDescriptionVersionModel::class, [ScreenAccess::toolDescription($tool, $this->override($tool))]);

        $data = $request->validate(CreateToolDescriptionVersionAction::rules());

        app(CreateToolDescriptionVersionAction::class)->execute($tool, $data);

        return redirect()
            ->route('atrium.cortex.tools.description', $tool)
            ->with('status', __('cortex::cortex.version_created'));
    }

    public function publish(string $tool, int $version): RedirectResponse
    {
        $this->assertRegistered($tool);

        $description = $this->override($tool);

        abort_if($description === null, 404);

        $this->authorizeScreen('publish', $description->versions()->where('version', $version)->firstOrFail());

        app(PublishToolDescriptionVersionAction::class)->execute($description, $version);

        return redirect()
            ->route('atrium.cortex.tools.description', $tool)
            ->with('status', __('cortex::cortex.version_published'));
    }

    public function destroy(string $tool): RedirectResponse
    {
        $this->assertRegistered($tool);

        $description = $this->override($tool);

        abort_if($description === null, 404);

        $this->authorizeScreen('delete', $description);

        app(DeleteToolDescriptionAction::class)->execute($description);

        return redirect()
            ->route('atrium.cortex.tools.description', $tool)
            ->with('status', __('cortex::cortex.override_removed'));
    }

    /**
     * The override row, or null when the tool still uses the description it
     * declares in code. The JSON API answers 404 here; a page needs the
     * distinction rather than an error.
     */
    private function override(string $tool): ?ToolDescriptionModel
    {
        return ToolDescriptionModel::query()
            ->where('tool', $tool)
            ->with('publishedVersion')
            ->first();
    }

    private function assertRegistered(string $tool): void
    {
        abort_unless(app(ToolRegistry::class)->has($tool), 404);
    }
}
