<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use RefactorCircus\Cortex\Tests\Fixtures\Missing\MissingParentFeature;

/**
 * A feature whose parent class is not installed, as CortexSupportFeature is
 * without refactor-circus/pennantplus: autoloading it throws.
 */
class OrphanedSupportFeature extends MissingParentFeature {}
