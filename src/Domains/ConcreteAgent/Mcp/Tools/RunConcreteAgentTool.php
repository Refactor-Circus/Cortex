<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\RunConcreteAgentMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Run a registered concrete agent with the given input and return its response text and token usage.')]
final class RunConcreteAgentTool extends Tool
{
    public function handle(RunConcreteAgentMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'agent' => $schema->string()->description('The registered concrete agent name.')->required(),
            'input' => $schema->string()->description('The user input to send to the agent.')->required(),
        ];
    }
}
