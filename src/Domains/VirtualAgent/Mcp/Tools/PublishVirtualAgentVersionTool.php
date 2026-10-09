<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\PublishVirtualAgentVersionMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Publish a specific prompt version of a Cortex virtual agent, making it the prompt the agent runs with.')]
final class PublishVirtualAgentVersionTool extends Tool
{
    public function handle(PublishVirtualAgentVersionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The virtual agent slug.')->required(),
            'version' => $schema->integer()->description('The version number to publish.')->min(1)->required(),
        ];
    }
}
