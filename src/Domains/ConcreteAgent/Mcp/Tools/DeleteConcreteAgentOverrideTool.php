<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\DeleteConcreteAgentOverrideMcpRequest;
use JayI\Cortex\Domains\Tool\Support\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Remove every override of a registered concrete agent (prompt versions and toolset); its code-declared prompt and tools take over.')]
final class DeleteConcreteAgentOverrideTool extends Tool
{
    public function handle(DeleteConcreteAgentOverrideMcpRequest $request): Response|ResponseFactory
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
