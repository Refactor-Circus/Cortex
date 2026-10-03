<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures;

use JayI\Cortex\Domains\McpServer\Support\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('Echo Server')]
#[Instructions('Code-declared echo server instructions.')]
final class EchoServer extends Server {}
