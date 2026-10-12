<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Responses\AgentResponse;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * @property AgentResponse $resource
 */
final class AgentRunResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->required(),
            'usage' => $schema->object([
                'input_tokens' => $schema->integer()->required(),
                'output_tokens' => $schema->integer()->required(),
                'cache_read_input_tokens' => $schema->integer()->nullable()->required(),
                'cache_write_input_tokens' => $schema->integer()->nullable()->required(),
                'reasoning_tokens' => $schema->integer()->nullable()->required(),
            ])->required(),
        ];
    }

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
