<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Support\Tables;

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
     * The graph is walked breadth-first, one pivot query per level.
     *
     * @param  list<string>  $subAgentIds
     */
    private function assertNoCycles(VirtualAgentModel $agent, array $subAgentIds): void
    {
        $target = (string) $agent->getKey();
        $seen = [];
        $level = array_values(array_unique($subAgentIds));

        while ($level !== []) {
            if (in_array($target, $level, true)) {
                throw ValidationException::withMessages([
                    'sub_agents' => 'The selected sub-agents would create a circular reference.',
                ]);
            }

            foreach ($level as $id) {
                $seen[$id] = true;
            }

            $next = [];

            foreach (DB::table(Tables::name('virtual_agent_sub_agents'))->whereIn('virtual_agent_id', $level)->pluck('sub_agent_id') as $subId) {
                $subId = (string) $subId;

                if (! isset($seen[$subId])) {
                    $next[$subId] = $subId;
                }
            }

            $level = array_values($next);
        }
    }
}
