<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\CreateVirtualAgentVersionMcpRequest;
use RefactorCircus\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a new immutable prompt version for a Cortex virtual agent. Not published unless requested.')]
final class CreateVirtualAgentVersionTool extends Tool
{
    public function handle(CreateVirtualAgentVersionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The virtual agent slug.')->required(),
            'content' => $schema->string()->description('The new version\'s content.')->required(),
            'publish' => $schema->boolean()->description('Publish this version immediately. Defaults to false.'),
        ];
    }
}
