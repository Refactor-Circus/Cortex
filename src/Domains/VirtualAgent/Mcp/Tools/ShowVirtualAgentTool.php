<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\ShowVirtualAgentMcpRequest;
use JayI\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Show a Cortex virtual agent by slug, including its published prompt, tools, and sub-agents.')]
final class ShowVirtualAgentTool extends Tool
{
    public function handle(ShowVirtualAgentMcpRequest $request): Response|ResponseFactory
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
