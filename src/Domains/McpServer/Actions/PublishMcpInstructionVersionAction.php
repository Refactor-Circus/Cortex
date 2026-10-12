<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\McpServer\Events\McpInstructionVersionPublishedActionEvent;
use RefactorCircus\Cortex\Domains\McpServer\Events\McpInstructionVersionPublishingActionEvent;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class PublishMcpInstructionVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    public function execute(McpInstructionModel $instruction, int $version): McpInstructionModel
    {
        McpInstructionVersionPublishingActionEvent::dispatch($instruction, $version);

        $result = $this->perform($instruction, $version);

        McpInstructionVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(McpInstructionModel $instruction, int $version): McpInstructionModel
    {
        return DB::transaction(function () use ($instruction, $version): McpInstructionModel {
            /** @var McpInstructionVersionModel $instructionVersion */
            $instructionVersion = $instruction->versions()->where('version', $version)->firstOrFail();

            $instruction->published_version_id = $instructionVersion->getKey();
            $instruction->save();

            DB::afterCommit(fn () => $this->cache->forget($this->cache->mcpInstructionsKey()));

            return $instruction->load('publishedVersion');
        });
    }
}
