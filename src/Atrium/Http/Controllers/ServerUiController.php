<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use RefactorCircus\Cortex\Domains\McpServer\Actions\ListMcpServersAction;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;

final class ServerUiController
{
    public function index(): View
    {
        /** @var view-string $view */
        $view = 'cortex::ui.servers.index';

        return view($view, [
            'servers' => app(ListMcpServersAction::class)->execute(),
            // Each row's instructions link is checked against its override.
            'instructions' => McpInstructionModel::query()->get()->keyBy('server'),
        ]);
    }
}
