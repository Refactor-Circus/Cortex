<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RefactorCircus\Cortex\Domains\Tool\Data\ListToolsData;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;

final class ToolUiController
{
    public function index(Request $request): View
    {
        $filters = ListToolsData::from($request->query());

        /** @var view-string $view */
        $view = 'cortex::ui.tools.index';

        return view($view, [
            // The list shows names and tags only, so no tool is instantiated.
            'tools' => app(ToolRegistry::class)->summaries($filters->tag),
            'tags' => app(ToolRegistry::class)->tags(),
            'tag' => $filters->tag,
            // Each row's description link is checked against its override.
            'descriptions' => ToolDescriptionModel::query()->get()->keyBy('tool'),
        ]);
    }
}
