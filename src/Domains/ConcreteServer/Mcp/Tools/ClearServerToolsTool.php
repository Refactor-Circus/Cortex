<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests\ClearServerToolsMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Clear the tool override of a registered MCP server, so it serves its code-declared tools again. Its instructions and their versions are kept. The cortex server cannot be overridden.')]
final class ClearServerToolsTool extends Tool
{
    public function handle(ClearServerToolsMcpRequest $request): Response|ResponseFactory
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
