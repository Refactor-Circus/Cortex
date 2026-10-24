<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ListMcpInstructionVersionsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionVersionResource;

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
