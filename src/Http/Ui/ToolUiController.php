<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use JayI\Cortex\Actions\ListToolsAction;
use JayI\Cortex\Models\ToolDescription;
use JayI\Cortex\Tools\ToolRegistry;

final class ToolUiController
{
    public function index(Request $request): View
    {
        /** @var array{tag?: string|null} $data */
        $data = $request->validate(ListToolsAction::rules());
        $tag = $data['tag'] ?? null;

        /** @var view-string $view */
        $view = 'cortex::ui.tools.index';

        return view($view, [
            'tools' => app(ListToolsAction::class)->execute($tag),
            'tags' => app(ToolRegistry::class)->tags(),
            'tag' => $tag,
            // Each row's description link is checked against its override.
            'descriptions' => ToolDescription::query()->get()->keyBy('tool'),
        ]);
    }
}
