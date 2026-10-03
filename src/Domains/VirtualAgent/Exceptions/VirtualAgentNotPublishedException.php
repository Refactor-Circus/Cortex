<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Exceptions;

use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RuntimeException;

final class VirtualAgentNotPublishedException extends RuntimeException
{
    public static function forAgent(VirtualAgentModel $agent): self
    {
        return new self("Virtual agent [{$agent->slug}] has no published prompt version.");
    }
}
