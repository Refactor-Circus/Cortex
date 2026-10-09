<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Laravel\Mcp\Request;

/**
 * A tool-specific request, the way the package's own MCP tools take theirs.
 */
final class EchoRequest extends Request
{
    public function message(): string
    {
        return (string) $this->get('message');
    }
}
