<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use Laravel\Ai\Responses\AgentResponse;

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
