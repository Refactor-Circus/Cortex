<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\McpServer\Actions\ListMcpInstructionVersionsAction;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\McpServer\Resources\McpInstructionVersionResource;

final class IndexMcpInstructionVersionsRequest extends McpInstructionRequest
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', McpInstructionVersionModel::class, [$this->instruction()]);
    }

    public function persist(): JsonResponse
    {
        $versions = app(ListMcpInstructionVersionsAction::class)->execute($this->instruction());

        return McpInstructionVersionResource::collection($versions)->response();
    }
}
