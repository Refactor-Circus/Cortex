<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;

/**
 * @mixin ConcreteAgentOverrideModel
 */
final class ConcreteAgentOverrideResource extends JsonResource
{
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
