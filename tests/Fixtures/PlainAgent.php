<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * A concrete agent that ignores Cortex overrides.
 */
final class PlainAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'Plain instructions.';
    }

    public function tools(): iterable
    {
        return [new EchoTool];
    }
}
