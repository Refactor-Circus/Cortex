<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionsListedActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionsListingActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;

final class ListVirtualAgentVersionsAction
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
     * @return LengthAwarePaginator<int, VirtualAgentVersionModel>
     */
    public function execute(VirtualAgentModel $agent, ?int $page = null): LengthAwarePaginator
    {
        VirtualAgentVersionsListingActionEvent::dispatch($agent, $page);

        $result = $this->perform($agent, $page);

        VirtualAgentVersionsListedActionEvent::dispatch($agent, $result);

        return $result;
    }

    /**
     * @return LengthAwarePaginator<int, VirtualAgentVersionModel>
     */
    private function perform(VirtualAgentModel $agent, ?int $page = null): LengthAwarePaginator
    {
        return $agent->versions()
            ->orderByDesc('version')
            ->paginate(page: $page);
    }
}
