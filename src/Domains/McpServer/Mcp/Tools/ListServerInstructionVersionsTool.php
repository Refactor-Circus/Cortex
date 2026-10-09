<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests\ListServerInstructionVersionsMcpRequest;
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
}
