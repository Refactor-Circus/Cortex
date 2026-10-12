<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\ListVirtualAgentsData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentsListedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentsListingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class ListVirtualAgentsAction
{
    /**
     * @return LengthAwarePaginator<int, VirtualAgentModel>
     */
    public function execute(ListVirtualAgentsData $filters = new ListVirtualAgentsData): LengthAwarePaginator
    {
        VirtualAgentsListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        VirtualAgentsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return LengthAwarePaginator<int, VirtualAgentModel>
     */
    private function perform(ListVirtualAgentsData $filters): LengthAwarePaginator
    {
        return VirtualAgentModel::query()
            ->with($filters->summary
                ? ['publishedVersion' => fn (BelongsTo $query): BelongsTo => $query->select(['id', 'version'])]
                : ['publishedVersion', 'subAgents'])
            ->orderBy('name')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }
}
