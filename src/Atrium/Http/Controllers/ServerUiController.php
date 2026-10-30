<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Cortex\Atrium\Screens;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ListMcpServersAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;

final class ServerUiController
{
    public function index(Request $request, ScreenExtensions $extensions): View
    {
        /** @var view-string $view */
        $view = 'cortex::ui.servers.index';

        return view($view, [
            'servers' => app(ListMcpServersAction::class)->execute(),
            // Each row's instructions link is checked against its override.
            'instructions' => McpInstructionModel::query()->get()->keyBy('server'),
            'resolved' => $extensions->resolve(Screens::declare('cortex.servers', Screen::LIST, null, McpInstructionModel::class), $request),
        ]);
    }
}
