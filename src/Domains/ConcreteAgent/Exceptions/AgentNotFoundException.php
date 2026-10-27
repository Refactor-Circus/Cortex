<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Exceptions;

use RefactorCircus\Cortex\Exceptions\CortexException;

final class AgentNotFoundException extends CortexException
{
    protected int $status = 404;

    public static function forName(string $name): self
    {
        return new self("Concrete agent [{$name}] is not registered.");
    }
}
