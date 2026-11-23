<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * Virtual and concrete agents were searched: the references the caller may
 * view.
 */
final class AgentsSearchedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<array{type: string, key: string, name: string}>  $results
     */
    public function __construct(
        public array $results,
    ) {}
}
