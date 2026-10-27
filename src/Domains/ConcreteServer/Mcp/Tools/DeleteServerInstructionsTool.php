<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests\DeleteServerInstructionsMcpRequest;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Remove the instruction override and its version history for a registered MCP server; the code-declared instructions take over. A tool override is kept: when the server still has one, the answer is its override row as structured content, otherwise a confirmation text.')]
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

    /**
     * Covers the structured answer, given when the row survives with its tool override.
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return McpInstructionResource::structuredSchema($schema);
    }
}
