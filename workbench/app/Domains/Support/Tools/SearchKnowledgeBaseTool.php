<?php

declare(strict_types=1);

namespace Workbench\App\Domains\Support\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\Tool\Support\Tool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Demo tool: answers with canned data so the workbench never calls out.
 */
#[Name('search-knowledge-base')]
#[Description('Search the help center articles for an answer to a customer question.')]
final class SearchKnowledgeBaseTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json(['query' => $request->get('query'), 'articles' => ['Tracking your order', 'Our returns policy']]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('What the customer is asking about.')->required(),
        ];
    }
}
