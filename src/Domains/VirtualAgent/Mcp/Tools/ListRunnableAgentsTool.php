<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources\AgentReferenceResource;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\ListRunnableAgentsMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List every agent the caller may run: virtual agents ordered by name, then registered concrete agents ordered by name. Each gives its type, the key to run it with (run-virtual-agent takes a virtual agent\'s slug, run-concrete-agent a concrete agent\'s registered name) and its name. Not paginated.')]
final class ListRunnableAgentsTool extends Tool
{
    public function handle(ListRunnableAgentsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()->items($schema->object(AgentReferenceResource::schema($schema)))->required(),
        ];
    }
}
