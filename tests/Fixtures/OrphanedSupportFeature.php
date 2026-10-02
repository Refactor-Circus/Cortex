<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures;

use JayI\Cortex\Tests\Fixtures\Missing\MissingParentFeature;

/**
 * A feature whose parent class is not installed, as CortexSupportFeature is
 * without jayi/pennantplus: autoloading it throws.
 */
class OrphanedSupportFeature extends MissingParentFeature {}
