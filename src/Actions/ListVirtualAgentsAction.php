<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Cortex\Events\Action\VirtualAgentsListedActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentsListingActionEvent;
use JayI\Cortex\Models\VirtualAgent;

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
     * @return LengthAwarePaginator<int, VirtualAgent>
     */
    public function execute(?int $page = null): LengthAwarePaginator
    {
        VirtualAgentsListingActionEvent::dispatch($page);

        $result = $this->perform($page);

        VirtualAgentsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return LengthAwarePaginator<int, VirtualAgent>
     */
    private function perform(?int $page = null): LengthAwarePaginator
    {
        return VirtualAgent::query()
            ->with(['publishedVersion', 'subAgents'])
            ->orderBy('name')
            ->paginate(page: $page);
    }
}
