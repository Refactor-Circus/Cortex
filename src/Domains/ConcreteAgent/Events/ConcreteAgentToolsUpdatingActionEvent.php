<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A concrete agent's toolset override is about to be set, or cleared when `$tools` is null.
 */
final class ConcreteAgentToolsUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<string>|null  $tools
     */
    public function __construct(
        public string $agent,
        public ?array $tools,
    ) {}
}
