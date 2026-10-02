<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use Illuminate\Contracts\View\View;
use JayI\Cortex\Actions\ListMcpServersAction;
use JayI\Cortex\Models\McpInstruction;

final class ServerUiController
{
    public function index(): View
    {
        /** @var view-string $view */
        $view = 'cortex::ui.servers.index';

        return view($view, [
            'servers' => app(ListMcpServersAction::class)->execute(),
            // Each row's instructions link is checked against its override.
            'instructions' => McpInstruction::query()->get()->keyBy('server'),
        ]);
    }
}
