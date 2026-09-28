<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Mcp\Requests\ListVirtualAgentVersionsMcpRequest;
use JayI\Cortex\Tools\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

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
