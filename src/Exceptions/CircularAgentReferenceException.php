<?php

declare(strict_types=1);

namespace JayI\Cortex\Exceptions;

use JayI\Cortex\Models\VirtualAgent;
use RuntimeException;

final class CircularAgentReferenceException extends RuntimeException
{
    public static function forAgent(VirtualAgent $agent): self
    {
        return new self("Virtual agent [{$agent->slug}] is part of a circular sub-agent reference.");
    }
}
