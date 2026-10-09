<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\ListVirtualAgentVersionsMcpRequest;
use RefactorCircus\Foundation\Mcp\Tool;

#[Description('List a Cortex virtual agent\'s prompt versions, newest first. Paginated.')]
final class ListVirtualAgentVersionsTool extends Tool
{
    public function handle(ListVirtualAgentVersionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The virtual agent slug.')->required(),
            'page' => $schema->integer()->description('Page number, starting at 1.')->min(1),
        ];
    }
}
