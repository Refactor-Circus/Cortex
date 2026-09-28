<?php

declare(strict_types=1);

namespace JayI\Cortex\Exceptions;

use JayI\Cortex\Models\VirtualAgent;
use RuntimeException;

final class VirtualAgentNotPublishedException extends RuntimeException
{
    public static function forAgent(VirtualAgent $agent): self
    {
        return new self("Virtual agent [{$agent->slug}] has no published prompt version.");
    }
}
