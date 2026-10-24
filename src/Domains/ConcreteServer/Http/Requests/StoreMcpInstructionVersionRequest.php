<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionVersionResource;

final class StoreMcpInstructionVersionRequest extends McpInstructionRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', McpInstructionVersionModel::class, [$this->instructionOrNew()]);
    }

    public function rules(): array
    {
        return CreateMcpInstructionVersionData::rules();
    }

    public function persist(): JsonResponse
    {
        $version = app(CreateMcpInstructionVersionAction::class)->execute($this->serverName(), CreateMcpInstructionVersionData::fromValidated($this->validated()));

        return (new McpInstructionVersionResource($version))->response()->setStatusCode(201);
    }
}
