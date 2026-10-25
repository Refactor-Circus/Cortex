<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Cortex\Domains\McpServer\Concerns\HasVersionedInstructions;

/**
 * A host server written before the ConcreteServer rename that cannot extend
 * Cortex's base class, so it uses the trait at its old name, which only the
 * compatibility shim resolves.
 */
#[Name('Old Trait Server')]
#[Instructions('Code-declared old trait server instructions.')]
final class OldTraitServer extends Server
{
    use HasVersionedInstructions;
}
