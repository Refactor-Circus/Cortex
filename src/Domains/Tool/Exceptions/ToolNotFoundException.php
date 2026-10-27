<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Exceptions;

use RefactorCircus\Cortex\Exceptions\CortexException;

final class ToolNotFoundException extends CortexException
{
    protected int $status = 404;

    public static function forName(string $name): self
    {
        return new self("Tool [{$name}] is not registered.");
    }
}
