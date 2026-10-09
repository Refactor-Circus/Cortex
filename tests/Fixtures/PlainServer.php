<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use RefactorCircus\Cortex\Domains\McpServer\Support\Server;

final class PlainServer extends Server
{
    protected string $instructions = 'Property-declared plain server instructions.';
}
