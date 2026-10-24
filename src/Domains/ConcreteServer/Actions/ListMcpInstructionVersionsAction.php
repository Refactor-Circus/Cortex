<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Actions;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionVersionsListedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionVersionsListingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;

final class ListMcpInstructionVersionsAction
{
    /**
     * @return Collection<int, McpInstructionVersionModel>
     */
    public function execute(McpInstructionModel $instruction): Collection
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
