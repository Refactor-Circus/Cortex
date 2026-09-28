<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Cortex\Models\ConcreteAgentOverride;

/**
 * @property array{name: string, class: string, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_instructions?: string, default_tools: list<string>, override: ConcreteAgentOverride|null} $resource
 */
final class ConcreteAgentResource extends JsonResource
{
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
