<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Contracts\Transport;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Support\Server;

/**
 * A host server that adds tools at runtime: `renamed-echo` in its
 * constructor and `echo-request-tool` (an instance) in `boot()`, on top of
 * its declared `echo-mcp-tool` and the never-registered `hidden-mcp-tool`.
 */
#[Instructions('Constructor tools server instructions.')]
final class ConstructorToolsServer extends Server
{
    /**
     * @var array<int, class-string<Tool>|Tool>
     */
    protected array $tools = [
        EchoMcpTool::class,
        HiddenMcpTool::class,
    ];

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);

        $this->tools[] = NamedMcpTool::class;
    }

    protected function boot(): void
    {
        $this->tools[] = new EchoRequestTool;
    }
}
