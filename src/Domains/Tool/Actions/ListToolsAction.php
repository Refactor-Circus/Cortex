<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use RefactorCircus\Cortex\Domains\Tool\Events\ToolsListedActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolsListingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use Laravel\Ai\Contracts\Tool;
use Laravel\Mcp\Server\Tool as McpTool;

final class ListToolsAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'tag' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function __construct(private readonly ToolRegistry $registry) {}

    /**
     * @return list<array{name: string, class: class-string<Tool>|class-string<McpTool>, description: string, schema: array<string, mixed>, tags: list<string>}>
     */
    public function execute(?string $tag = null): array
    {
        ToolsListingActionEvent::dispatch($tag);

        $result = $this->perform($tag);

        ToolsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return list<array{name: string, class: class-string<Tool>|class-string<McpTool>, description: string, schema: array<string, mixed>, tags: list<string>}>
     */
    private function perform(?string $tag): array
    {
        return $this->registry->all($tag);
    }
}
