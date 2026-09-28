<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Mcp\Requests\UpdateVirtualAgentMcpRequest;
use JayI\Cortex\Mcp\Tools\Concerns\DescribesVirtualAgentPayload;
use JayI\Cortex\Tools\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Update a Cortex virtual agent. The tools and sub-agent lists replace the current lists entirely. Changed instructions are saved as a new prompt version and published.')]
final class UpdateVirtualAgentTool extends Tool
{
    use DescribesVirtualAgentPayload;

    public function handle(UpdateVirtualAgentMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The agent slug.')->required(),
            'name' => $schema->string()->description('New display name.'),
            'instructions' => $schema->string()->description('New prompt. Saved as a new published version when it differs from the current one.'),
            ...$this->agentPayloadSchema($schema),
        ];
    }
}
