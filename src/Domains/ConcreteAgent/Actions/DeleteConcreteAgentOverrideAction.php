<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentOverrideDeletedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentOverrideDeletingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class DeleteConcreteAgentOverrideAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

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
        $override->delete();

        $this->cache->forget($this->cache->concreteAgentsKey());
    }
}
