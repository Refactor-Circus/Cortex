<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Actions;

use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishedActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishingActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Cortex\Support\PublicationCache;

final class PublishVirtualAgentVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(VirtualAgentModel $agent, int $version): VirtualAgentModel
    {
        VirtualAgentVersionPublishingActionEvent::dispatch($agent, $version);

        $result = $this->perform($agent, $version);

        VirtualAgentVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(VirtualAgentModel $agent, int $version): VirtualAgentModel
    {
        /** @var VirtualAgentVersionModel $agentVersion */
        $agentVersion = $agent->versions()->where('version', $version)->firstOrFail();

        $agent->published_version_id = (string) $agentVersion->getKey();
        $agent->save();

        $this->cache->forget($this->cache->virtualAgentKey((string) $agent->getKey()));

        return $agent->load(['publishedVersion', 'subAgents']);
    }
}
