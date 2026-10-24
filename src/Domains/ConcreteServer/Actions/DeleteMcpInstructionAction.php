<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionDeletedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionDeletingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class DeleteMcpInstructionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    public function execute(McpInstructionModel $instruction): void
    {
        McpInstructionDeletingActionEvent::dispatch($instruction);

        $this->perform($instruction);

        McpInstructionDeletedActionEvent::dispatch($instruction);
    }

    private function perform(McpInstructionModel $instruction): void
    {
        DB::transaction(function () use ($instruction): void {
            $instruction->delete();

            DB::afterCommit(fn () => $this->cache->forget($this->cache->mcpInstructionsKey()));
        });
    }
}
