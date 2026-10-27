<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\ListVirtualAgentsData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentsListedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentsListingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListVirtualAgentsAction extends Action
{
    /**
     * @return LengthAwarePaginator<int, VirtualAgentModel>
     */
    protected function handle(ListVirtualAgentsData $filters = new ListVirtualAgentsData): LengthAwarePaginator
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
