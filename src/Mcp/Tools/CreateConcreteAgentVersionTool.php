<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Mcp\Requests\CreateConcreteAgentVersionMcpRequest;
use JayI\Cortex\Tools\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a new immutable prompt override version for a registered concrete agent. Not published unless requested.')]
final class CreateConcreteAgentVersionTool extends Tool
{
    public function handle(CreateConcreteAgentVersionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'agent' => $schema->string()->description('The registered concrete agent name.')->required(),
            'content' => $schema->string()->description('The new version\'s prompt.')->required(),
            'publish' => $schema->boolean()->description('Publish this version immediately. Defaults to false.'),
        ];
    }
}
