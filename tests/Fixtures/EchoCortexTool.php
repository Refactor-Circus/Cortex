<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\Tool\Support\Tool;

#[Description('Echoes back the given message from the Cortex base tool.')]
final class EchoCortexTool extends Tool
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
