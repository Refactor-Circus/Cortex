<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpToolOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerToolResolver;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * A registered MCP server with its effective instructions and tools.
 *
 * `tools` is what the server serves, by stored entry and in stored order
 * while a tool override applies, else its code tools by name;
 * `missing_tools` and `ambiguous_tools` name the stored entries that serve
 * nothing and those that also name a registered Cortex tool (see
 * {@see ServerToolResolver::report()}).
 *
 * @property array{name: string, class: string, instructions: string} $resource
 */
final class McpServerResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'instructions' => $schema->string()->required(),
            'tools' => $schema->array()->items($schema->string())->required(),
            'tools_overridden' => $schema->boolean()->required(),
            'missing_tools' => $schema->array()->items($schema->string())->required(),
            'ambiguous_tools' => $schema->array()->items($schema->string())->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = $this->resource['name'];

        return [
            'name' => $name,
            'instructions' => $this->resource['instructions'],
            ...app(ServerToolResolver::class)->report($name, app(McpToolOverrides::class)->for($name)),
        ];
    }
}
