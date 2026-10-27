<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ListMcpServersAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Http\Resources\McpServerResource;
use RefactorCircus\Cortex\Http\Request;

final class IndexMcpServersRequest extends Request
{
    public function persist(): JsonResponse
    {
        $servers = app(ListMcpServersAction::class)->execute();

        return McpServerResource::collection($servers)->response();
    }
}
