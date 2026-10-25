<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\DeleteMcpInstructionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionResource;

final class DeleteMcpInstructionRequest extends McpInstructionRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->instruction());
    }

    /**
     * 204 when the override row goes with its instructions; 200 with the row
     * when it survives because it still holds a tool override (D-057).
     */
    public function persist(): Response|JsonResponse
    {
        $surviving = app(DeleteMcpInstructionAction::class)->execute($this->instruction());

        if ($surviving === null) {
            return response()->noContent();
        }

        return (new McpInstructionResource($surviving))->response()->setStatusCode(200);
    }
}
