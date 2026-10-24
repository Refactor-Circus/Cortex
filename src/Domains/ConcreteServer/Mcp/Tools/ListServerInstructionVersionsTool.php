<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests\ListServerInstructionVersionsMcpRequest;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionVersionResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List the instruction versions of a registered MCP server, newest first.')]
final class ListServerInstructionVersionsTool extends Tool
{
    public function handle(ListServerInstructionVersionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server' => $schema->string()->description('The registered MCP server name.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()->items($schema->object(McpInstructionVersionResource::schema($schema)))->required(),
        ];
    }
}
