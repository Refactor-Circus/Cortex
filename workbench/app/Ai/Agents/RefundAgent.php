<?php

declare(strict_types=1);

namespace Workbench\App\Ai\Agents;

use JayI\Cortex\Agents\Agent;
use JayI\Cortex\Agents\Attributes\LockedTools;
use Workbench\App\Domains\Orders\Tools\LookupOrderTool;
use Workbench\App\Domains\Orders\Tools\RefundOrderTool;

/**
 * Demo concrete agent that moves money, so its toolset is locked in code.
 */
#[LockedTools]
final class RefundAgent extends Agent
{
    public function defaultInstructions(): string
    {
        return 'You process refund requests. Look the order up first and refund only delivered orders under 90 days old.';
    }

    public function defaultTools(): iterable
    {
        return [new LookupOrderTool, new RefundOrderTool];
    }
}
