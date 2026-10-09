<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests;

use RefactorCircus\Cortex\Domains\McpServer\Actions\ListMcpServersAction;
use RefactorCircus\Cortex\Domains\McpServer\Http\Resources\McpServerResource;
use RefactorCircus\Cortex\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class ListServersMcpRequest extends Request
{
    protected function handle(array $validated): ResponseFactory
    {
        $servers = app(ListMcpServersAction::class)->execute();

        return $this->structuredCollection(McpServerResource::collection($servers)->resolve());
    }
}
