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
 * A Cortex tool registered under the name of a code tool (`echo-mcp-tool`),
 * so a test can show the code tool wins for a stored entry naming both.
 */
#[Name('echo-mcp-tool')]
#[Description('A registered tool that reuses a code tool name.')]
final class CollidingCortexTool extends Tool
{
    public const string ANSWER = 'Served by the colliding registered tool.';

    public function handle(Request $request): Response
    {
        return Response::text(self::ANSWER);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()->description('Ignored.'),
        ];
    }
}
