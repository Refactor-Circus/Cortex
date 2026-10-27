<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Exceptions;

use RefactorCircus\Cortex\Exceptions\CortexException;

/**
 * A class registered as a MCP server is not one.
 */
final class InvalidMcpServerClassException extends CortexException
{
    public static function forClass(string $class, string $expected): self
    {
        return new self(sprintf('MCP server [%s] must extend %s.', $class, $expected));
    }
}
