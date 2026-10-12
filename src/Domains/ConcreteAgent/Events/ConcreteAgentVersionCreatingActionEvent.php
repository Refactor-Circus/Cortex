<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\CreateConcreteAgentVersionData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A new version of a concrete agent's prompt override is about to be created.
 */
final class ConcreteAgentVersionCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $agent,
        public CreateConcreteAgentVersionData $data,
    ) {}
}
