<?php

declare(strict_types=1);

namespace Workbench\App\Domains\Support\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use RefactorCircus\Cortex\Domains\Tool\Support\Tool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Demo tool: answers with canned data so the workbench never calls out.
 */
#[Name('create-ticket')]
#[Description('Open a support ticket for a human agent to follow up on.')]
final class CreateTicketTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json(['ticket' => 'T-'.random_int(1000, 9999), 'summary' => $request->get('summary')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->description('A one-line summary of the problem.')->required(),
        ];
    }
}
