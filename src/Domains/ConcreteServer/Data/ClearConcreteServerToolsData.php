<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ClearConcreteServerToolsAction, which restores a concrete MCP
 * server's code tools. The server comes from the route or tool argument;
 * clearing takes no input of its own.
 */
final readonly class ClearConcreteServerToolsData extends Data
{
    /**
     * Nothing to validate: the server is checked by the request (unknown
     * names answer 404) and by the action (the `cortex` server is refused).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $server): array
    {
        return [];
    }
}
