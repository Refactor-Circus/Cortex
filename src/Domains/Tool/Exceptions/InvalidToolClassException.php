<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Exceptions;

use RefactorCircus\Cortex\Exceptions\CortexException;

/**
 * A class registered as a tool is not one.
 */
final class InvalidToolClassException extends CortexException
{
    public static function forClass(string $class, string $expected): self
    {
        return new self(sprintf('Tool [%s] must implement %s.', $class, $expected));
    }
}
