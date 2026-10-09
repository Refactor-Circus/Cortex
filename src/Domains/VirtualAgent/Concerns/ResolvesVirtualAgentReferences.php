<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

trait ResolvesVirtualAgentReferences
{
    /**
     * @param  list<string>  $slugs
     * @return list<string>
     */
    private function subAgentIds(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return array_values(array_map(
            fn (int|string $id): string => (string) $id,
            VirtualAgentModel::query()->whereIn('slug', $slugs)->pluck('id')->all(),
        ));
    }

    /**
     * Reject sub-agent selections that would create a cycle back to the agent.
     *
     * @param  list<string>  $subAgentIds
     */
    private function assertNoCycles(VirtualAgentModel $agent, array $subAgentIds): void
    {
        $queue = $subAgentIds;
        $seen = [];

        while ($queue !== []) {
            $id = array_shift($queue);

            if (in_array($id, $seen, true)) {
                continue;
            }

            $seen[] = $id;

            if ($id === (string) $agent->getKey()) {
                throw ValidationException::withMessages([
                    'sub_agents' => 'The selected sub-agents would create a circular reference.',
                ]);
            }

            foreach (DB::table('cortex_virtual_agent_sub_agents')->where('virtual_agent_id', $id)->pluck('sub_agent_id') as $subId) {
                $queue[] = (string) $subId;
            }
        }
    }
}
