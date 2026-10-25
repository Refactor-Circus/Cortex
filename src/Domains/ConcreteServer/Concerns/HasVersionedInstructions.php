<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Concerns;

/**
 * The trait's name from before it served tool overrides: it uses
 * {@see HasCortexOverrides}, so servers using it get the published
 * instructions override and the stored tool override alike.
 */
trait HasVersionedInstructions
{
    use HasCortexOverrides;
}
