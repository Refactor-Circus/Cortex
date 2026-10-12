<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\CreateVirtualAgentVersionData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionCreatedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionCreatingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class CreateVirtualAgentVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    public function execute(VirtualAgentModel $agent, CreateVirtualAgentVersionData $data): VirtualAgentVersionModel
    {
        VirtualAgentVersionCreatingActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        VirtualAgentVersionCreatedActionEvent::dispatch($agent, $result);

        return $result;
    }

    private function perform(VirtualAgentModel $agent, CreateVirtualAgentVersionData $data): VirtualAgentVersionModel
    {
        return DB::transaction(function () use ($agent, $data): VirtualAgentVersionModel {
            // Lock the parent row, not the aggregate: PostgreSQL refuses FOR UPDATE with MAX().
            $agent->newQuery()->whereKey($agent->getKey())->lockForUpdate()->value($agent->getKeyName());

            /** @var VirtualAgentVersionModel $version */
            $version = $agent->versions()->create([
                'version' => ((int) $agent->versions()->max('version')) + 1,
                'content' => $data->content,
            ]);

            if ($data->publish) {
                $agent->published_version_id = (string) $version->getKey();
                $agent->save();

                $agent->unsetRelation('publishedVersion');

                DB::afterCommit(fn () => $this->cache->forget($this->cache->virtualAgentKey((string) $agent->getKey())));
            }

            return $version;
        });
    }
}
