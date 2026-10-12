<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * @mixin VirtualAgentModel
 */
final class VirtualAgentResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'slug' => $schema->string()->required(),
            'description' => $schema->string()->nullable()->required(),
            'instructions' => $schema->string()->nullable()->description('The published prompt, when loaded.'),
            'published_version' => $schema->integer()->nullable()->description('The published version number, when loaded.'),
            'provider' => $schema->string()->nullable()->required(),
            'model' => $schema->string()->nullable()->required(),
            'settings' => $schema->object([
                'temperature' => $schema->number(),
                'max_steps' => $schema->integer(),
                'max_tokens' => $schema->integer(),
                'top_p' => $schema->number(),
            ])->nullable()->required(),
            'tools' => $schema->array()->items($schema->string())->required(),
            'sub_agents' => $schema->array()->items($schema->string())->description('Sub-agent slugs, when loaded.'),
            'concrete_sub_agents' => $schema->array()->items($schema->string())->required(),
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'instructions' => $this->whenLoaded('publishedVersion', fn (): ?string => $this->publishedVersion?->content),
            'published_version' => $this->whenLoaded('publishedVersion', fn (): ?int => $this->publishedVersion?->version),
            'provider' => $this->provider,
            'model' => $this->model,
            'settings' => $this->settings,
            'tools' => $this->tools,
            'sub_agents' => $this->whenLoaded(
                'subAgents',
                fn (): array => $this->subAgents->pluck('slug')->values()->all(),
            ),
            'concrete_sub_agents' => $this->concrete_sub_agents,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
