<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Actions;

use JayI\Cortex\Domains\McpServer\Events\McpInstructionVersionPublishedActionEvent;
use JayI\Cortex\Domains\McpServer\Events\McpInstructionVersionPublishingActionEvent;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use JayI\Cortex\Support\PublicationCache;

final class PublishMcpInstructionVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(McpInstructionModel $instruction, int $version): McpInstructionModel
    {
        McpInstructionVersionPublishingActionEvent::dispatch($instruction, $version);

        $result = $this->perform($instruction, $version);

        McpInstructionVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(McpInstructionModel $instruction, int $version): McpInstructionModel
    {
        /** @var McpInstructionVersionModel $instructionVersion */
        $instructionVersion = $instruction->versions()->where('version', $version)->firstOrFail();

        $instruction->published_version_id = $instructionVersion->getKey();
        $instruction->save();

        $this->cache->forget($this->cache->mcpInstructionsKey());

        return $instruction->load('publishedVersion');
    }
}
