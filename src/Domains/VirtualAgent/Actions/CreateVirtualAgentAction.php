<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\VirtualAgent\Concerns\ResolvesVirtualAgentReferences;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\CreateVirtualAgentData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class CreateVirtualAgentAction
{
    use ResolvesVirtualAgentReferences;

    public function execute(CreateVirtualAgentData $data): VirtualAgentModel
    {
        VirtualAgentCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        VirtualAgentCreatedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Create the agent with its prompt as published version 1.
     */
    private function perform(CreateVirtualAgentData $data): VirtualAgentModel
    {
        return DB::transaction(function () use ($data): VirtualAgentModel {
            $agent = VirtualAgentModel::query()->create([
                'name' => $data->name,
                'slug' => $data->slug,
                'description' => $data->description,
                'provider' => $data->provider,
                'model' => $data->model,
                'settings' => $data->settings,
                'tools' => $data->tools,
                'concrete_sub_agents' => $data->concreteSubAgents,
            ]);

            $version = $agent->versions()->create([
                'version' => 1,
                'content' => $data->instructions,
            ]);

            $agent->published_version_id = (string) $version->getKey();
            $agent->save();

            $agent->subAgents()->sync($this->subAgentIds($data->subAgents));

            return $agent->load(['publishedVersion', 'subAgents']);
        });
    }
}
