<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Exceptions;

use RefactorCircus\Cortex\Exceptions\CortexException;

/**
 * A class registered as a agent is not one.
 */
final class InvalidAgentClassException extends CortexException
{
    public static function forClass(string $class, string $expected): self
    {
        return new self(sprintf('Agent [%s] must implement %s.', $class, $expected));
    }
}
