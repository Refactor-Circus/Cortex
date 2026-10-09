<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use RefactorCircus\Cortex\Domains\Tool\Mcp\Requests\ListToolsMcpRequest;
use RefactorCircus\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('List the tools registered with Cortex that can be attached to agents, including their input schemas and tags. Pass a tag to list only the tools carrying it.')]
final class ListToolsTool extends Tool
{
    public function handle(ListToolsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tag' => $schema->string()->description('Only list tools carrying this tag, e.g. "orders".'),
        ];
    }
}
