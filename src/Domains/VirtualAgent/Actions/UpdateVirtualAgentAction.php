<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\VirtualAgent\Concerns\ResolvesVirtualAgentReferences;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\CreateVirtualAgentVersionData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\UpdateVirtualAgentData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentUpdatedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentUpdatingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Keystone\Actions\Action;

final class UpdateVirtualAgentAction extends Action
{
    use ResolvesVirtualAgentReferences;

    public function __construct(private readonly CreateVirtualAgentVersionAction $createVersion) {}

    protected function handle(VirtualAgentModel $agent, UpdateVirtualAgentData $data): VirtualAgentModel
    {
        VirtualAgentUpdatingActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        VirtualAgentUpdatedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Changed instructions become a new published prompt version; unchanged
     * instructions leave the version history alone.
     */
    private function perform(VirtualAgentModel $agent, UpdateVirtualAgentData $data): VirtualAgentModel
    {
        return DB::transaction(function () use ($agent, $data): VirtualAgentModel {
            $agent->fill(
                Arr::only($data->toArray(), ['name', 'description', 'provider', 'model', 'settings', 'tools', 'concrete_sub_agents']),
            )->save();

            if (is_array($data->subAgents)) {
                /** @var list<string> $slugs */
                $slugs = $data->subAgents;

                $subAgentIds = $this->subAgentIds($slugs);

                $this->assertNoCycles($agent, $subAgentIds);

                $agent->subAgents()->sync($subAgentIds);
            }

            if (is_string($data->instructions) && $data->instructions !== $agent->publishedVersion?->content) {
                $this->createVersion->execute($agent, new CreateVirtualAgentVersionData(content: $data->instructions, publish: true));
            }

            return $agent->refresh()->load(['publishedVersion', 'subAgents']);
        });
    }
}
