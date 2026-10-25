<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests\UpdateServerToolsMcpRequest;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Override the tools a registered MCP server serves with a list picked from its own code tools and the registered Cortex tools, served in the order given. An empty list serves no tools; null stores no list, so the code-declared tools are served. The cortex server cannot be overridden.')]
final class UpdateServerToolsTool extends Tool
{
    public function handle(UpdateServerToolsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server' => $schema->string()->description('The registered MCP server name.')->required(),
            'tools' => $schema->array()->items($schema->string())->nullable()
                ->description('Tool names to serve, in order, or null to serve the tools declared in code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return McpInstructionResource::structuredSchema($schema);
    }
}
