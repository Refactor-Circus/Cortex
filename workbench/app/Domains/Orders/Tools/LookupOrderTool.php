<?php

declare(strict_types=1);

namespace Workbench\App\Domains\Orders\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Cortex\Domains\Tool\Support\Tool;

/**
 * Demo tool: answers with canned data so the workbench never calls out.
 */
#[Name('lookup-order')]
#[Description('Look up an order by its number and return its status, items and shipping details.')]
final class LookupOrderTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json(['order' => $request->get('order_number'), 'status' => 'shipped', 'carrier' => 'UPS', 'items' => 3]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()->description('The order number, such as A-10042.')->required(),
        ];
    }
}
