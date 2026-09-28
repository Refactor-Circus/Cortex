<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;

/**
 * The registered concrete agents were listed.
 */
final class ConcreteAgentsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<array<string, mixed>>  $agents
     */
    public function __construct(
        public array $agents,
    ) {}
}
