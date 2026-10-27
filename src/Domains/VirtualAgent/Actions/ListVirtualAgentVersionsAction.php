<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\ListVirtualAgentVersionsData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionsListedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionsListingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListVirtualAgentVersionsAction extends Action
{
    /**
     * @return LengthAwarePaginator<int, VirtualAgentVersionModel>
     */
    protected function handle(VirtualAgentModel $agent, ListVirtualAgentVersionsData $filters = new ListVirtualAgentVersionsData): LengthAwarePaginator
    {
        VirtualAgentVersionsListingActionEvent::dispatch($agent, $filters);

        $result = $this->perform($agent, $filters);

        VirtualAgentVersionsListedActionEvent::dispatch($agent, $result);

        return $result;
    }

    /**
     * @return LengthAwarePaginator<int, VirtualAgentVersionModel>
     */
    private function perform(VirtualAgentModel $agent, ListVirtualAgentVersionsData $filters): LengthAwarePaginator
    {
        return $agent->versions()
            ->orderByDesc('version')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }
}
