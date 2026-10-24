<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Actions;

use Laravel\Mcp\Server as McpServer;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpServersListedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpServersListingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;

final class ListMcpServersAction
{
    public function __construct(private readonly McpServerRegistry $registry) {}

    /**
     * @return list<array{name: string, class: class-string<McpServer>, instructions: string}>
     */
    public function execute(): array
    {
        McpServersListingActionEvent::dispatch();

        $result = $this->perform();

        McpServersListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return list<array{name: string, class: class-string<McpServer>, instructions: string}>
     */
    private function perform(): array
    {
        return $this->registry->all();
    }
}
