<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\Cortex\Events\Action\VirtualAgentVersionsListedActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentVersionsListingActionEvent;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;

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
     * @return LengthAwarePaginator<int, VirtualAgentVersion>
     */
    public function execute(VirtualAgent $agent, ?int $page = null): LengthAwarePaginator
    {
        VirtualAgentVersionsListingActionEvent::dispatch($agent, $page);

        $result = $this->perform($agent, $page);

        VirtualAgentVersionsListedActionEvent::dispatch($agent, $result);

        return $result;
    }

    /**
     * @return LengthAwarePaginator<int, VirtualAgentVersion>
     */
    private function perform(VirtualAgent $agent, ?int $page = null): LengthAwarePaginator
    {
        return $agent->versions()
            ->orderByDesc('version')
            ->paginate(page: $page);
    }
}
