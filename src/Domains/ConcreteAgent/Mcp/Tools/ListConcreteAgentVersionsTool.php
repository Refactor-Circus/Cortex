<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\ListConcreteAgentVersionsMcpRequest;
use JayI\Cortex\Domains\Tool\Support\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List the prompt override versions of a registered concrete agent, newest first.')]
final class ListConcreteAgentVersionsTool extends Tool
{
    public function handle(ListConcreteAgentVersionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'agent' => $schema->string()->description('The registered concrete agent name.')->required(),
        ];
    }
}
