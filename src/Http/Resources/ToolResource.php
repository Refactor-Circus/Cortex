<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{name: string, class: string, description: string, schema: array<string, mixed>, tags: list<string>} $resource
 */
final class ToolResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'description' => $this->resource['description'],
            'schema' => $this->resource['schema'],
            'tags' => $this->resource['tags'],
        ];
    }
}
