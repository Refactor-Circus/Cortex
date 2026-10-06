<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\ListConcreteAgentsMcpRequest;
use JayI\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List the concrete (class-based) agents registered with Cortex, with the prompt and tools each currently runs with and whether they are overridden.')]
final class ListConcreteAgentsTool extends Tool
{
    public function handle(ListConcreteAgentsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
