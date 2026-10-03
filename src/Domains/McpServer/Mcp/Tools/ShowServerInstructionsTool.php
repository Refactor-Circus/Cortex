<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\McpServer\Mcp\Requests\ShowServerInstructionsMcpRequest;
use JayI\Cortex\Domains\Tool\Support\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

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
}
