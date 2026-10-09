<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class EchoTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Echoes back the given message.';
    }

    public function handle(Request $request): Stringable|string
    {
        return (string) $request['message'];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()->description('The message to echo.')->required(),
        ];
    }
}
