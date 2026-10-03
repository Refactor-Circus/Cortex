<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Actions;

use JayI\Cortex\Domains\McpServer\Events\McpServersListedActionEvent;
use JayI\Cortex\Domains\McpServer\Events\McpServersListingActionEvent;
use JayI\Cortex\Domains\McpServer\Services\McpServerRegistry;
use Laravel\Mcp\Server as McpServer;

final class ListMcpServersAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

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
