<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;

/**
 * @mixin ToolDescriptionModel
 */
final class ToolDescriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tool' => $this->tool,
            'published_version' => $this->publishedVersion?->version,
            'published_content' => $this->publishedVersion?->content,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
