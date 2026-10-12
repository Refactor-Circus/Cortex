<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * @property array{name: string, class: string, description: string, schema: array<string, mixed>, tags: list<string>} $resource
 */
final class ToolResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'schema' => $schema->object()->description('The tool\'s input schema.')->required(),
            'tags' => $schema->array()->items($schema->string())->required(),
        ];
    }

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
