<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;

/**
 * @mixin ToolDescriptionVersionModel
 */
final class ToolDescriptionVersionResource extends JsonResource
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
