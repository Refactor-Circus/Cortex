<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Actions;

use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionShowingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionShownActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;

final class ShowMcpInstructionAction
{
    public function execute(string $server): McpInstructionModel
    {
        McpInstructionShowingActionEvent::dispatch($server);

        $result = $this->perform($server);

        McpInstructionShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(string $server): McpInstructionModel
    {
        return McpInstructionModel::query()
            ->where('server', $server)
            ->with('publishedVersion')
            ->firstOrFail();
    }
}
