<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Request as McpRequest;
use Stringable;

/**
 * A `Laravel\Ai` tool with its own gating: it is offered only to a signed-in
 * user, read from the MCP request it is asked with. It records what it was
 * asked, so a test can show the request context reaches it.
 */
final class GatedAiTool implements Tool
{
    public static bool $asked = false;

    public static ?Authenticatable $seenUser = null;

    public static function reset(): void
    {
        self::$asked = false;
        self::$seenUser = null;
    }

    public function name(): string
    {
        return 'gated-ai-tool';
    }

    public function shouldRegister(McpRequest $request): bool
    {
        self::$asked = true;
        self::$seenUser = $request->user();

        return self::$seenUser !== null;
    }

    public function description(): Stringable|string
    {
        return 'Echoes back the given message to signed-in users.';
    }

    public function handle(Request $request): Stringable|string
    {
        return 'gated: '.$request->string('message');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()->description('The message to echo.')->required(),
        ];
    }
}
