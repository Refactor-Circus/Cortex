<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\McpServer\Mcp\Requests\PublishServerInstructionVersionMcpRequest;
use JayI\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Publish a specific instruction version of a registered MCP server, replacing its code-declared instructions.')]
final class PublishServerInstructionVersionTool extends Tool
{
    public function handle(PublishServerInstructionVersionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server' => $schema->string()->description('The registered MCP server name.')->required(),
            'version' => $schema->integer()->description('The version number to publish.')->min(1)->required(),
        ];
    }
}
