<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Actions;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Cortex\Domains\McpServer\Events\McpInstructionVersionsListedActionEvent;
use RefactorCircus\Cortex\Domains\McpServer\Events\McpInstructionVersionsListingActionEvent;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;

final class ListMcpInstructionVersionsAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

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
