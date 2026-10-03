<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

/**
 * @mixin VirtualAgentModel
 */
final class VirtualAgentResource extends JsonResource
{
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
