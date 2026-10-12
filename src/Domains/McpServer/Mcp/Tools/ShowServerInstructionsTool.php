<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests\ShowServerInstructionsMcpRequest;
use RefactorCircus\Cortex\Domains\McpServer\Resources\McpInstructionResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Show the instruction override for a registered MCP server, including its published version.')]
final class ShowServerInstructionsTool extends Tool
{
    public function handle(ShowServerInstructionsMcpRequest $request): Response|ResponseFactory
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
        return McpInstructionResource::structuredSchema($schema);
    }
}
