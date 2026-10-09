<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentDeletedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentDeletingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class DeleteVirtualAgentAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(VirtualAgentModel $agent): void
    {
        VirtualAgentDeletingActionEvent::dispatch($agent);

        $this->perform($agent);

        VirtualAgentDeletedActionEvent::dispatch($agent);
    }

    /**
     * Deleting the agent takes its prompt versions and sub-agent links with it.
     */
    private function perform(VirtualAgentModel $agent): void
    {
        $agent->delete();

        $this->cache->forget($this->cache->virtualAgentKey((string) $agent->getKey()));
    }
}
