<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use RefactorCircus\Cortex\Domains\VirtualAgent\Concerns\DescribesVirtualAgentPayload;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\CreateVirtualAgentMcpRequest;
use RefactorCircus\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a Cortex virtual agent. Its instructions become prompt version 1, published immediately. Attach registered tools and virtual or concrete sub-agents to delegate to.')]
final class CreateVirtualAgentTool extends Tool
{
    use DescribesVirtualAgentPayload;

    public function handle(CreateVirtualAgentMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Display name for the agent.')->required(),
            'slug' => $schema->string()->description('Unique identifier (letters, numbers, dashes, underscores).')->required(),
            'instructions' => $schema->string()->description('The agent\'s prompt.')->required(),
            ...$this->agentPayloadSchema($schema),
        ];
    }
}
