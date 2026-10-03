<?php

declare(strict_types=1);

namespace Workbench\App\Domains\Catalog\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\Tool\Support\Tool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Demo tool: answers with canned data so the workbench never calls out.
 */
#[Name('check-inventory')]
#[Description('Check how many units of a product are in stock in each warehouse.')]
final class CheckInventoryTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json(['sku' => $request->get('sku'), 'warehouses' => ['east' => 42, 'west' => 7]]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sku' => $schema->string()->description('The product SKU.')->required(),
        ];
    }
}
