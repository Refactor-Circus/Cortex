<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * The agents a caller may run were listed: references to the virtual
 * agents, then the concrete agents.
 */
final class RunnableAgentsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<array{type: string, key: string, name: string}>  $agents
     */
    public function __construct(
        public array $agents,
    ) {}
}
