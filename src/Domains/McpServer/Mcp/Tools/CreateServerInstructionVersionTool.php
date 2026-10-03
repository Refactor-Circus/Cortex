<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\McpServer\Mcp\Requests\CreateServerInstructionVersionMcpRequest;
use JayI\Cortex\Domains\Tool\Support\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a new immutable instruction version for a registered MCP server. Not published unless requested.')]
final class CreateServerInstructionVersionTool extends Tool
{
    public function handle(CreateServerInstructionVersionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server' => $schema->string()->description('The registered MCP server name.')->required(),
            'content' => $schema->string()->description('The new version\'s instructions.')->required(),
            'publish' => $schema->boolean()->description('Publish this version immediately. Defaults to false.'),
        ];
    }
}
