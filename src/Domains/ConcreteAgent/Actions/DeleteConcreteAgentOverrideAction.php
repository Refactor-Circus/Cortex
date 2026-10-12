<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentOverrideDeletedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentOverrideDeletingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class DeleteConcreteAgentOverrideAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    public function execute(ConcreteAgentOverrideModel $override): void
    {
        ConcreteAgentOverrideDeletingActionEvent::dispatch($override);

        $this->perform($override);

        ConcreteAgentOverrideDeletedActionEvent::dispatch($override);
    }

    /**
     * Removes the prompt versions and the toolset override together.
     */
    private function perform(ConcreteAgentOverrideModel $override): void
    {
        DB::transaction(function () use ($override): void {
            $override->delete();

            DB::afterCommit(fn () => $this->cache->forget($this->cache->concreteAgentsKey()));
        });
    }
}
