<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * @mixin ConcreteAgentOverrideModel
 */
final class ConcreteAgentOverrideResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'agent' => $schema->string()->required(),
            'published_version' => $schema->integer()->nullable()->required(),
            'published_content' => $schema->string()->nullable()->required(),
            'tools' => $schema->array()->items($schema->string())->nullable()->required(),
            'created_at' => $schema->string()->nullable()->required(),
            'updated_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'agent' => $this->agent,
            'published_version' => $this->publishedVersion?->version,
            'published_content' => $this->publishedVersion?->content,
            'tools' => $this->tools,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
