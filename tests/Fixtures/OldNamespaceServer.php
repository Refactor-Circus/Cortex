<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Cortex\Domains\McpServer\Support\Server;

/**
 * A host server written before the ConcreteServer rename: it extends the
 * base class at its old name, which only the compatibility shim resolves.
 */
#[Name('Old Namespace Server')]
#[Instructions('Code-declared old namespace server instructions.')]
final class OldNamespaceServer extends Server {}
