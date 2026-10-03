<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{name: string, class: string, instructions: string} $resource
 */
final class McpServerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'instructions' => $this->resource['instructions'],
        ];
    }
}
