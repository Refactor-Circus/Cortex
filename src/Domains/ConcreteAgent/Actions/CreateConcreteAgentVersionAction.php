<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\CreateConcreteAgentVersionData;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionCreatedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionCreatingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Keystone\Actions\Action;

final class CreateConcreteAgentVersionAction extends Action
{
    public function __construct(private readonly PublicationCache $cache) {}

    protected function handle(string $agent, CreateConcreteAgentVersionData $data): ConcreteAgentOverrideVersionModel
    {
        ConcreteAgentVersionCreatingActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        ConcreteAgentVersionCreatedActionEvent::dispatch($agent, $result);

        return $result;
    }

    private function perform(string $agent, CreateConcreteAgentVersionData $data): ConcreteAgentOverrideVersionModel
    {
        return DB::transaction(function () use ($agent, $data): ConcreteAgentOverrideVersionModel {
            /** @var ConcreteAgentOverrideModel $override */
            $override = ConcreteAgentOverrideModel::query()->firstOrCreate(['agent' => $agent]);

            // Lock the parent row, not the aggregate: PostgreSQL refuses FOR UPDATE with MAX().
            $override->newQuery()->whereKey($override->getKey())->lockForUpdate()->value($override->getKeyName());

            /** @var ConcreteAgentOverrideVersionModel $version */
            $version = $override->versions()->create([
                'version' => ((int) $override->versions()->max('version')) + 1,
                'content' => $data->content,
            ]);

            if ($data->publish) {
                $override->published_version_id = (string) $version->getKey();
                $override->save();

                DB::afterCommit(fn () => $this->cache->forget($this->cache->concreteAgentsKey()));
            }

            return $version;
        });
    }
}
