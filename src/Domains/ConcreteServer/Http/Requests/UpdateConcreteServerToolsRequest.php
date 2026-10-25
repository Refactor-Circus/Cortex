<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionResource;

final class UpdateConcreteServerToolsRequest extends McpInstructionRequest
{
    public function authorize(): bool
    {
        return $this->allows('updateTools', $this->instructionOrNew());
    }

    public function rules(): array
    {
        return UpdateConcreteServerToolsData::rules($this->serverName());
    }

    public function persist(): JsonResponse
    {
        $instruction = app(UpdateConcreteServerToolsAction::class)->execute(
            $this->serverName(),
            UpdateConcreteServerToolsData::fromValidated($this->validated()),
        );

        // PUT sets state, so it answers 200 even when it first creates the row.
        return (new McpInstructionResource($instruction))->response()->setStatusCode(200);
    }
}
