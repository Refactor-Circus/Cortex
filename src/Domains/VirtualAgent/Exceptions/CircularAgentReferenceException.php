<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Exceptions;

use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RuntimeException;

final class CircularAgentReferenceException extends RuntimeException
{
    public static function forAgent(VirtualAgentModel $agent): self
    {
        return new self("Virtual agent [{$agent->slug}] is part of a circular sub-agent reference.");
    }
}
