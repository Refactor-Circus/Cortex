<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\ListVirtualAgentsMcpRequest;
use RefactorCircus\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List Cortex virtual agents with their published prompts, tools, and sub-agents. Paginated.')]
final class ListVirtualAgentsTool extends Tool
{
    public function handle(ListVirtualAgentsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'page' => $schema->integer()->description('Page number, starting at 1.')->min(1),
        ];
    }
}
