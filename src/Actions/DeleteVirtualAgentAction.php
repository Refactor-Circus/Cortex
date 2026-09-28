<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use JayI\Cortex\Events\Action\VirtualAgentDeletedActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentDeletingActionEvent;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Support\PublicationCache;

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

    public function execute(VirtualAgent $agent): void
    {
        VirtualAgentDeletingActionEvent::dispatch($agent);

        $this->perform($agent);

        VirtualAgentDeletedActionEvent::dispatch($agent);
    }

    /**
     * Deleting the agent takes its prompt versions and sub-agent links with it.
     */
    private function perform(VirtualAgent $agent): void
    {
        $agent->delete();

        $this->cache->forget($this->cache->virtualAgentKey((string) $agent->getKey()));
    }
}
