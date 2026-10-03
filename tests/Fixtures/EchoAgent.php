<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures;

use JayI\Cortex\Domains\ConcreteAgent\Support\Agent;

/**
 * A concrete agent whose prompt and toolset Cortex can override.
 */
final class EchoAgent extends Agent
{
    public function defaultInstructions(): string
    {
        return 'Echo everything back.';
    }

    public function defaultTools(): iterable
    {
        return [new EchoTool];
    }
}
