<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\UpdateConcreteAgentToolsMcpRequest;
use RefactorCircus\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Override the toolset of a registered concrete agent with a list picked from its own code tools and the registered Cortex tools, or pass null to restore its code-declared toolset. Agents marked #[LockedTools] only accept null.')]
final class UpdateConcreteAgentToolsTool extends Tool
{
    public function handle(UpdateConcreteAgentToolsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'agent' => $schema->string()->description('The registered concrete agent name.')->required(),
            'tools' => $schema->array()->items($schema->string())->nullable()
                ->description('Tool names to give the agent, or null to use the toolset declared in code.')->required(),
        ];
    }
}
