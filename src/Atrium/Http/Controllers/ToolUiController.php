<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RefactorCircus\Cortex\Domains\Tool\Actions\ListToolsAction;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;

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
            'descriptions' => ToolDescriptionModel::query()->get()->keyBy('tool'),
        ]);
    }
}
