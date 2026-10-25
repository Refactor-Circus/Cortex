<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

/**
 * A registered Cortex tool whose construction throws, so a test can show a
 * tool override skips the entry, logs it and serves the rest.
 */
final class ThrowingCortexTool extends Tool
{
    public const string MESSAGE = 'The throwing tool cannot be built.';

    public function __construct()
    {
        throw new RuntimeException(self::MESSAGE);
    }

    public function handle(Request $request): Response
    {
        return Response::text('Never reached.');
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
