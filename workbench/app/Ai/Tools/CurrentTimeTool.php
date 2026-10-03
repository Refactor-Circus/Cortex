<?php

declare(strict_types=1);

namespace Workbench\App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Demo laravel/ai tool, not an MCP tool, to show both kinds side by side.
 */
final class CurrentTimeTool implements Tool
{
    public function name(): string
    {
        return 'current-time';
    }

    public function description(): Stringable|string
    {
        return 'Tell the current date and time in a given timezone.';
    }

    public function handle(Request $request): Stringable|string
    {
        return now((string) ($request['timezone'] ?? 'UTC'))->toDayDateTimeString();
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'timezone' => $schema->string()->description('An IANA timezone, such as Europe/London.'),
        ];
    }
}
