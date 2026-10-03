<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Mcp\Requests;

use JayI\Cortex\Domains\McpServer\Actions\ListMcpServersAction;
use JayI\Cortex\Domains\McpServer\Http\Resources\McpServerResource;
use JayI\Cortex\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class ListServersMcpRequest extends Request
{
    protected function handle(array $validated): ResponseFactory
    {
        $servers = app(ListMcpServersAction::class)->execute();

        return $this->structuredCollection(McpServerResource::collection($servers)->resolve());
    }
}
