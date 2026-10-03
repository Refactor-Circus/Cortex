<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\Tool\Support\Tool;
use JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\RunVirtualAgentMcpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Run a Cortex virtual agent with the given input and return its response text and token usage.')]
final class RunVirtualAgentTool extends Tool
{
    public function handle(RunVirtualAgentMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The virtual agent slug.')->required(),
            'input' => $schema->string()->description('The user input to send to the agent.')->required(),
        ];
    }
}
