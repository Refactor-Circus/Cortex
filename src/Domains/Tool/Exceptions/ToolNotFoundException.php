<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Exceptions;

use InvalidArgumentException;

final class ToolNotFoundException extends InvalidArgumentException
{
    public static function forName(string $name): self
    {
        return new self("Tool [{$name}] is not registered.");
    }
}
