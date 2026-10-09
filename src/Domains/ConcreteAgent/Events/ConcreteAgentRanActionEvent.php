<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laravel\Ai\Responses\AgentResponse;
use RefactorCircus\Foundation\Contracts\ActionFinishedEvent;

/**
 * A concrete agent ran and responded.
 */
final class ConcreteAgentRanActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $agent,
        public string $input,
        public AgentResponse $response,
    ) {}
}
