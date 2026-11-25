<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * A reference to a virtual or concrete agent, as search and the runnable
 * agents list return it: its kind, the key its routes take (a virtual
 * agent's slug, a concrete agent's registered name) and its name.
 *
 * @property array{type: string, key: string, name: string} $resource
 */
final class AgentReferenceResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(['virtual_agent', 'concrete_agent'])->required(),
            'key' => $schema->string()->required(),
            'name' => $schema->string()->required(),
        ];
    }

    /**
     * @return array{type: string, key: string, name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource['type'],
            'key' => $this->resource['key'],
            'name' => $this->resource['name'],
        ];
    }
}
