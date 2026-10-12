<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * @property array{name: string, class: string, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_instructions?: string, default_tools: list<string>, override: ConcreteAgentOverrideModel|null} $resource
 */
final class ConcreteAgentResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'overridable' => $schema->boolean()->required(),
            'tools_overridable' => $schema->boolean()->required(),
            'instructions' => $schema->string()->required(),
            'tools' => $schema->array()->items($schema->string())->required(),
            'default_instructions' => $schema->string()->nullable()->description('The instructions declared in code, on a single agent.'),
            'default_tools' => $schema->array()->items($schema->string())->required(),
            'published_version' => $schema->integer()->nullable()->required(),
            'tools_overridden' => $schema->boolean()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $override = $this->resource['override'];

        return [
            'name' => $this->resource['name'],
            'overridable' => $this->resource['overridable'],
            'tools_overridable' => $this->resource['tools_overridable'],
            'instructions' => $this->resource['instructions'],
            'tools' => $this->resource['tools'],
            'default_instructions' => $this->when(
                array_key_exists('default_instructions', $this->resource),
                fn (): ?string => $this->resource['default_instructions'] ?? null,
            ),
            'default_tools' => $this->resource['default_tools'],
            'published_version' => $override?->publishedVersion?->version,
            'tools_overridden' => $override?->tools !== null,
        ];
    }
}
