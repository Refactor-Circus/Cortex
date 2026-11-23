<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;

/**
 * A summary of Cortex's settings: the providers configured in
 * `cortex.providers`, how many tools, concrete agents and MCP servers are
 * registered, and whether the cache is enabled.
 *
 * @property array{providers: list<array{name: string, models: list<string>}>, tool_count: int, agent_count: int, server_count: int, cache_enabled: bool} $resource
 */
final class SettingsSummaryResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'providers' => $schema->array()->items($schema->object([
                'name' => $schema->string()->required(),
                'models' => $schema->array()->items($schema->string())->required(),
            ]))->description('The providers configured in cortex.providers, with their models.')->required(),
            'tool_count' => $schema->integer()->description('How many tools are registered.')->required(),
            'agent_count' => $schema->integer()->description('How many concrete agents are registered.')->required(),
            'server_count' => $schema->integer()->description('How many MCP servers are registered.')->required(),
            'cache_enabled' => $schema->boolean()->description('Whether Cortex caches published content.')->required(),
        ];
    }

    /**
     * @return array{providers: list<array{name: string, models: list<string>}>, tool_count: int, agent_count: int, server_count: int, cache_enabled: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'providers' => $this->resource['providers'],
            'tool_count' => $this->resource['tool_count'],
            'agent_count' => $this->resource['agent_count'],
            'server_count' => $this->resource['server_count'],
            'cache_enabled' => $this->resource['cache_enabled'],
        ];
    }
}
