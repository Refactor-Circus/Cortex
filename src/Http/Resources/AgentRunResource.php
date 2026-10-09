<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Ai\Responses\AgentResponse;

/**
 * @property AgentResponse $resource
 */
final class AgentRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'text' => $this->resource->text,
            'usage' => $this->resource->usage->toArray(),
        ];
    }
}
