<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\ConcreteServer\Http\Resources\McpServerResource;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests\ListServersMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List the MCP servers registered with Cortex, including the instructions each currently serves.')]
final class ListServersTool extends Tool
{
    public function handle(ListServersMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()->items($schema->object(McpServerResource::schema($schema)))->required(),
        ];
    }
}
