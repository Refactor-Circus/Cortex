<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\McpServer\Actions\ListMcpServersAction;
use JayI\Cortex\Domains\McpServer\Http\Resources\McpServerResource;
use JayI\Cortex\Http\Request;

final class IndexMcpServersRequest extends Request
{
    public function rules(): array
    {
        return ListMcpServersAction::rules();
    }

    public function persist(): JsonResponse
    {
        $servers = app(ListMcpServersAction::class)->execute();

        return McpServerResource::collection($servers)->response();
    }
}
