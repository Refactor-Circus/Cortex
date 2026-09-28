<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

/**
 * A new version of a concrete agent's prompt override was created, and published when asked.
 */
final class ConcreteAgentVersionCreatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $agent,
        public ConcreteAgentOverrideVersion $version,
    ) {}
}
