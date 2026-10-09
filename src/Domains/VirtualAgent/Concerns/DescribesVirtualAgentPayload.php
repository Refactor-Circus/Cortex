<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

trait DescribesVirtualAgentPayload
{
    /**
     * Schema for the virtual agent fields shared by the create and update tools.
     *
     * @return array<string, Type>
     */
    private function agentPayloadSchema(JsonSchema $schema): array
    {
        return [
            'description' => $schema->string()->description('Optional description of the agent\'s purpose. Also shown to parent agents when this agent is a sub-agent.'),
            'provider' => $schema->string()->description('AI provider name (e.g. anthropic, openai). Defaults to the app\'s ai.default config.'),
            'model' => $schema->string()->description('Model identifier for the provider.'),
            'settings' => $schema->object([
                'temperature' => $schema->number()->description('Sampling temperature (0-2).'),
                'max_steps' => $schema->integer()->description('Maximum agentic tool-use steps.'),
                'max_tokens' => $schema->integer()->description('Maximum output tokens.'),
                'top_p' => $schema->number()->description('Nucleus sampling threshold (0-1).'),
            ])->description('Generation settings.'),
            'tools' => $schema->array()->items($schema->string())
                ->description('Registered tool names available to the agent. Replaces the whole list.'),
            'sub_agents' => $schema->array()->items($schema->string())
                ->description('Slugs of virtual agents to delegate to as sub-agents. Replaces the whole list.'),
            'concrete_sub_agents' => $schema->array()->items($schema->string())
                ->description('Names of registered concrete agents to delegate to as sub-agents. Replaces the whole list.'),
        ];
    }
}
