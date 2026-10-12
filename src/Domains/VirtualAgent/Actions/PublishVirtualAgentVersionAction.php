<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class PublishVirtualAgentVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    public function execute(VirtualAgentModel $agent, int $version): VirtualAgentModel
    {
        VirtualAgentVersionPublishingActionEvent::dispatch($agent, $version);

        $result = $this->perform($agent, $version);

        VirtualAgentVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(VirtualAgentModel $agent, int $version): VirtualAgentModel
    {
        return DB::transaction(function () use ($agent, $version): VirtualAgentModel {
            /** @var VirtualAgentVersionModel $agentVersion */
            $agentVersion = $agent->versions()->where('version', $version)->firstOrFail();

            $agent->published_version_id = (string) $agentVersion->getKey();
            $agent->save();

            DB::afterCommit(fn () => $this->cache->forget($this->cache->virtualAgentKey((string) $agent->getKey())));

            return $agent->load(['publishedVersion', 'subAgents']);
        });
    }
}
