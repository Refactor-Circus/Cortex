<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\McpServer\Mcp\Requests\DeleteServerInstructionsMcpRequest;
use JayI\Cortex\Domains\Tool\Support\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Remove the instruction override and its version history for a registered MCP server; the code-declared instructions take over.')]
final class DeleteServerInstructionsTool extends Tool
{
    public function handle(DeleteServerInstructionsMcpRequest $request): Response|ResponseFactory
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
