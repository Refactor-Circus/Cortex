<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

/**
 * An MCP tool whose `name()` differs from its class basename
 * (`named-mcp-tool`), so a test can tell which of the two is used.
 */
#[Name('renamed-echo')]
#[Description('Echoes back the given message under a declared name.')]
final class NamedMcpTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::text((string) $request->get('message'));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()->description('The message to echo.')->required(),
        ];
    }
}
