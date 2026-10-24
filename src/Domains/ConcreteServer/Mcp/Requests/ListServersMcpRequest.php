<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ListMcpServersAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Http\Resources\McpServerResource;
use RefactorCircus\Cortex\Mcp\Request;

final class ListServersMcpRequest extends Request
{
    protected function handle(array $validated): ResponseFactory
    {
        $servers = app(ListMcpServersAction::class)->execute();

        return McpServerResource::collection($servers)->toStructuredResponse();
    }
}
