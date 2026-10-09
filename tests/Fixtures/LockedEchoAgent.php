<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use RefactorCircus\Cortex\Domains\ConcreteAgent\Support\Agent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Support\LockedTools;

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
