<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures;

use JayI\Cortex\Agents\Agent;
use JayI\Cortex\Agents\Attributes\LockedTools;

/**
 * A concrete agent whose prompt Cortex manages but whose tools stay fixed.
 */
#[LockedTools]
final class LockedEchoAgent extends Agent
{
    public function defaultInstructions(): string
    {
        return 'Echo everything back, carefully.';
    }

    public function defaultTools(): iterable
    {
        return [new EchoTool];
    }
}
