<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Mcp\Requests\PublishConcreteAgentVersionMcpRequest;
use JayI\Cortex\Tools\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Publish a specific prompt override version of a registered concrete agent, replacing its code-declared prompt.')]
final class PublishConcreteAgentVersionTool extends Tool
{
    public function handle(PublishConcreteAgentVersionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'agent' => $schema->string()->description('The registered concrete agent name.')->required(),
            'version' => $schema->integer()->description('The version number to publish.')->min(1)->required(),
        ];
    }
}
