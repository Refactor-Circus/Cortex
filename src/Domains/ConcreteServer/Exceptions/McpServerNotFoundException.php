<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Exceptions;

use RefactorCircus\Cortex\Exceptions\CortexException;

final class McpServerNotFoundException extends CortexException
{
    protected int $status = 404;

    public static function forName(string $name): self
    {
        return new self("MCP server [{$name}] is not registered.");
    }
}
