<?php

declare(strict_types=1);

namespace JayI\Cortex\Exceptions;

use InvalidArgumentException;

final class AgentNotFoundException extends InvalidArgumentException
{
    public static function forName(string $name): self
    {
        return new self("Concrete agent [{$name}] is not registered.");
    }
}
