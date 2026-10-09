<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Cortex\Domains\McpServer\Support\Server;

#[Name('Echo Server')]
#[Instructions('Code-declared echo server instructions.')]
final class EchoServer extends Server {}
