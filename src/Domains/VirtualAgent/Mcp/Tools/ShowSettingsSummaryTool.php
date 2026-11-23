<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources\SettingsSummaryResource;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\ShowSettingsSummaryMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Show a summary of Cortex\'s settings: the configured providers with their models, how many tools, concrete agents and MCP servers are registered, and whether the cache is enabled.')]
final class ShowSettingsSummaryTool extends Tool
{
    public function handle(ShowSettingsSummaryMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SettingsSummaryResource::structuredSchema($schema);
    }
}
