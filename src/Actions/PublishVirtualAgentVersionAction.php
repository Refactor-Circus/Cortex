<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use JayI\Cortex\Events\Action\VirtualAgentVersionPublishedActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentVersionPublishingActionEvent;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;
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

    public function execute(VirtualAgent $agent, int $version): VirtualAgent
    {
        VirtualAgentVersionPublishingActionEvent::dispatch($agent, $version);

        $result = $this->perform($agent, $version);

        VirtualAgentVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(VirtualAgent $agent, int $version): VirtualAgent
    {
        /** @var VirtualAgentVersion $agentVersion */
        $agentVersion = $agent->versions()->where('version', $version)->firstOrFail();

        $agent->published_version_id = (string) $agentVersion->getKey();
        $agent->save();

        $this->cache->forget($this->cache->virtualAgentKey((string) $agent->getKey()));

        return $agent->load(['publishedVersion', 'subAgents']);
    }
}
