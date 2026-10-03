<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\Tool\Support\Tool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

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
