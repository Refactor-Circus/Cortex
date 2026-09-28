<?php

declare(strict_types=1);

namespace JayI\Cortex\Agents\Attributes;

use Attribute;

/**
 * Keep a concrete agent's toolset as declared in code. Cortex still manages
 * its prompt, but refuses toolset overrides and ignores any saved earlier.
 * Use it on agents whose safety depends on the exact tools they hold.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class LockedTools {}
