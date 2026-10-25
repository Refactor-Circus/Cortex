<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

/**
 * An MCP tool that never registers (`shouldRegister()` is false), so a test
 * can show a tool override keeps a selected tool's own gating.
 */
#[Description('A tool that is never offered to clients.')]
final class HiddenMcpTool extends Tool
{
    public function shouldRegister(): bool
    {
        return false;
    }

    public function handle(Request $request): Response
    {
        return Response::text('The hidden tool ran.');
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
