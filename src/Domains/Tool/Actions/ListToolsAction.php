<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use Laravel\Ai\Contracts\Tool;
use Laravel\Mcp\Server\Tool as McpTool;
use RefactorCircus\Cortex\Domains\Tool\Data\ListToolsData;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolsListedActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolsListingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;

final class ListToolsAction
{
    public function __construct(private readonly ToolRegistry $registry) {}

    /**
     * @return list<array{name: string, class: class-string<Tool>|class-string<McpTool>, description: string, schema: array<string, mixed>, tags: list<string>}>
     */
    public function execute(ListToolsData $filters = new ListToolsData): array
    {
        ToolsListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        ToolsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return list<array{name: string, class: class-string<Tool>|class-string<McpTool>, description: string, schema: array<string, mixed>, tags: list<string>}>
     */
    private function perform(ListToolsData $filters): array
    {
        return $this->registry->all($filters->tag);
    }
}
