<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Support;

use RefactorCircus\Cortex\Domains\ConcreteAgent\Concerns\HasCortexOverrides;
use Laravel\Ai\Contracts\Agent as AgentContract;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Base class for concrete agents whose prompt and toolset Cortex manages:
 * declare them in defaultInstructions() and defaultTools(), register the
 * class with the AgentRegistry (via the `cortex.agents` config or at
 * runtime), and the agent runs with the published Cortex overrides when
 * they exist, falling back to the code-declared ones otherwise.
 */
abstract class Agent implements AgentContract, HasTools
{
    use HasCortexOverrides;
    use Promptable;
}
