<?php

declare(strict_types=1);

namespace Workbench\App\Domains\Orders\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Tools\Tool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Demo tool: answers with canned data so the workbench never calls out.
 */
#[Name('refund-order')]
#[Description('Refund an order in full to its original payment method.')]
final class RefundOrderTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json(['order' => $request->get('order_number'), 'refunded' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()->description('The order number to refund.')->required(),
        ];
    }
}
