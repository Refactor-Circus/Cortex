<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Exceptions;

use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Exceptions\CortexException;

final class CircularAgentReferenceException extends CortexException
{
    public static function forAgent(VirtualAgentModel $agent): self
    {
        return new self("Virtual agent [{$agent->slug}] is part of a circular sub-agent reference.");
    }
}
