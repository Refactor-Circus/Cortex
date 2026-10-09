<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\DeleteVirtualAgentMcpRequest;
use RefactorCircus\Foundation\Mcp\Tool;

#[Description('Delete a Cortex virtual agent and its prompt versions. Sub-agent links are removed; the linked agents themselves are kept.')]
final class DeleteVirtualAgentTool extends Tool
{
    public function handle(DeleteVirtualAgentMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The virtual agent slug.')->required(),
        ];
    }
}
