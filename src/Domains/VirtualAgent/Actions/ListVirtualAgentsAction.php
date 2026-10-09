<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentsListedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentsListingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class ListVirtualAgentsAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return LengthAwarePaginator<int, VirtualAgentModel>
     */
    public function execute(?int $page = null): LengthAwarePaginator
    {
        VirtualAgentsListingActionEvent::dispatch($page);

        $result = $this->perform($page);

        VirtualAgentsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return LengthAwarePaginator<int, VirtualAgentModel>
     */
    private function perform(?int $page = null): LengthAwarePaginator
    {
        return VirtualAgentModel::query()
            ->with(['publishedVersion', 'subAgents'])
            ->orderBy('name')
            ->paginate(page: $page);
    }
}
