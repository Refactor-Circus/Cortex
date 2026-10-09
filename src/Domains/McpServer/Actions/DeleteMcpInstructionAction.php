<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Actions;

use RefactorCircus\Cortex\Domains\McpServer\Events\McpInstructionDeletedActionEvent;
use RefactorCircus\Cortex\Domains\McpServer\Events\McpInstructionDeletingActionEvent;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class DeleteMcpInstructionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(McpInstructionModel $instruction): void
    {
        McpInstructionDeletingActionEvent::dispatch($instruction);

        $this->perform($instruction);

        McpInstructionDeletedActionEvent::dispatch($instruction);
    }

    private function perform(McpInstructionModel $instruction): void
    {
        $instruction->delete();

        $this->cache->forget($this->cache->mcpInstructionsKey());
    }
}
