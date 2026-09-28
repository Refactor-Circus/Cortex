<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use JayI\Cortex\Events\Action\ConcreteAgentOverrideDeletedActionEvent;
use JayI\Cortex\Events\Action\ConcreteAgentOverrideDeletingActionEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Support\PublicationCache;

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

    public function execute(ConcreteAgentOverride $override): void
    {
        ConcreteAgentOverrideDeletingActionEvent::dispatch($override);

        $this->perform($override);

        ConcreteAgentOverrideDeletedActionEvent::dispatch($override);
    }

    /**
     * Removes the prompt versions and the toolset override together.
     */
    private function perform(ConcreteAgentOverride $override): void
    {
        $override->delete();

        $this->cache->forget($this->cache->concreteAgentsKey());
    }
}
