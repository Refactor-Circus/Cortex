<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\McpServer\Actions\ShowMcpInstructionAction;
use RefactorCircus\Cortex\Domains\McpServer\Resources\McpInstructionResource;

final class ShowMcpInstructionRequest extends McpInstructionRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->instruction());
    }

    public function persist(): JsonResponse
    {
        $instruction = app(ShowMcpInstructionAction::class)->execute($this->serverName());

        return (new McpInstructionResource($instruction))->response();
    }
}
