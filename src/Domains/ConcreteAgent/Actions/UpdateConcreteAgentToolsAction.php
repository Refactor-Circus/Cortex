<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\UpdateConcreteAgentToolsData;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentToolsUpdatedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentToolsUpdatingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Keystone\Actions\Action;

final class UpdateConcreteAgentToolsAction extends Action
{
    public function __construct(private readonly PublicationCache $cache) {}

    protected function handle(string $agent, UpdateConcreteAgentToolsData $data): ConcreteAgentOverrideModel
    {
        ConcreteAgentToolsUpdatingActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        ConcreteAgentToolsUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(string $agent, UpdateConcreteAgentToolsData $data): ConcreteAgentOverrideModel
    {
        return DB::transaction(function () use ($agent, $data): ConcreteAgentOverrideModel {
            /** @var ConcreteAgentOverrideModel $override */
            $override = ConcreteAgentOverrideModel::query()->firstOrNew(['agent' => $agent]);

            $override->tools = $data->tools;
            $override->save();

            DB::afterCommit(fn () => $this->cache->forget($this->cache->concreteAgentsKey()));

            return $override->load('publishedVersion');
        });
    }
}
