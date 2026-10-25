<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;
use RefactorCircus\Cortex\Domains\ConcreteServer\Support\Server;

/**
 * A host server whose code tools mix a plain entry with a ToolSearch group.
 * NamedMcpTool declares its own name (`renamed-echo`), so the fixture shows
 * that tools are named by `name()`, not by their class basename.
 */
#[Instructions('Grouped tools server instructions.')]
final class GroupedToolsServer extends Server
{
    /**
     * @var array<int|string, class-string<Tool>|array<int, class-string<Tool>>>
     */
    protected array $tools = [
        EchoMcpTool::class,
        ToolSearch::class => [
            EchoRequestTool::class,
            NamedMcpTool::class,
        ],
    ];
}
