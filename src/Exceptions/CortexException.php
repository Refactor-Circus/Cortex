<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Exceptions;

use RefactorCircus\Keystone\Exceptions\PackageException;

/**
 * Base of every exception Cortex throws, so callers can catch Cortex failures
 * broadly or by their concrete class. Renders as JSON with its message; the
 * status is 409 unless a subclass sets another.
 */
abstract class CortexException extends PackageException {}
