<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Exceptions;

/**
 * A stored version was about to be changed in place; versions are
 * append-only, so a change is always a new version.
 */
final class ImmutableVersionException extends CortexException
{
    public static function for(string $versions): self
    {
        return new self("{$versions} versions are immutable. Create a new version instead.");
    }
}
