<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Actions;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionVersionsListedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionVersionsListingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListMcpInstructionVersionsAction extends Action
{
    /**
     * @return Collection<int, McpInstructionVersionModel>
     */
    protected function handle(McpInstructionModel $instruction): Collection
    {
        McpInstructionVersionsListingActionEvent::dispatch($instruction);

        $result = $this->perform($instruction);

        McpInstructionVersionsListedActionEvent::dispatch($instruction, $result);

        return $result;
    }

    /**
     * @return Collection<int, McpInstructionVersionModel>
     */
    private function perform(McpInstructionModel $instruction): Collection
    {
        return $instruction->versions()->orderByDesc('version')->get();
    }
}
