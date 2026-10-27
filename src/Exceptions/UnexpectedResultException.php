<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Exceptions;

/**
 * Cortex's own code broke one of its invariants: a bug, not something the
 * caller did, so it answers 500.
 */
final class UnexpectedResultException extends CortexException
{
    protected int $status = 500;

    public static function pagedListing(): self
    {
        return new self('A paged listing answers a page.');
    }
}
