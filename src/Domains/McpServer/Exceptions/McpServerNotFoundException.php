<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Exceptions;

use InvalidArgumentException;

final class McpServerNotFoundException extends InvalidArgumentException
{
    public static function forName(string $name): self
    {
        return new self("MCP server [{$name}] is not registered.");
    }
}
