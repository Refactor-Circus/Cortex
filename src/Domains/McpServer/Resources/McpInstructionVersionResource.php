<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;

/**
 * @mixin McpInstructionVersionModel
 */
final class McpInstructionVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'version' => $this->version,
            'content' => $this->content,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
