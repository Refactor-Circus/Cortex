<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources\AgentReferenceResource;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\SearchAgentsMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Search Cortex agents by name: up to 5 virtual agents whose name or slug contains the query, ordered by name, then up to 5 registered concrete agents whose name contains it (case-insensitive), ordered by name. Each result gives its type, the key its tools take (a virtual agent\'s slug, a concrete agent\'s registered name) and its name. Only the kinds and agents the caller may view are searched. Not paginated.')]
final class SearchAgentsTool extends Tool
{
    public function handle(SearchAgentsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()->description('Text the agent name (or virtual agent slug) must contain, at most 255 characters. Matched literally: % and _ are not wildcards.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()->items($schema->object(AgentReferenceResource::schema($schema)))->required(),
        ];
    }
}
