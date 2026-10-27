<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerToolResolver;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * A server's override row: its published instructions and its tools, with
 * the same tool fields as {@see McpServerResource} (see
 * {@see ServerToolResolver::report()}), taken from the row's own `tools`.
 *
 * @mixin McpInstructionModel
 */
final class McpInstructionResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'server' => $schema->string()->required(),
            'published_version' => $schema->integer()->nullable()->required(),
            'published_content' => $schema->string()->nullable()->required(),
            'tools' => $schema->array()->items($schema->string())->required(),
            'tools_overridden' => $schema->boolean()->required(),
            'missing_tools' => $schema->array()->items($schema->string())->required(),
            'ambiguous_tools' => $schema->array()->items($schema->string())->required(),
            'created_at' => $schema->string()->nullable()->required(),
            'updated_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'server' => $this->server,
            'published_version' => $this->publishedVersion?->version,
            'published_content' => $this->publishedVersion?->content,
            ...app(ServerToolResolver::class)->report($this->server, $this->tools),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
