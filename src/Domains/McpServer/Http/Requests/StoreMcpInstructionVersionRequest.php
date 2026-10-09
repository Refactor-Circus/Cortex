<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\McpServer\Resources\McpInstructionVersionResource;

final class StoreMcpInstructionVersionRequest extends McpInstructionRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', McpInstructionVersionModel::class, [$this->instructionOrNew()]);
    }

    public function rules(): array
    {
        return CreateMcpInstructionVersionAction::rules();
    }

    public function persist(): JsonResponse
    {
        $version = app(CreateMcpInstructionVersionAction::class)->execute($this->serverName(), $this->validated());

        return (new McpInstructionVersionResource($version))->response()->setStatusCode(201);
    }
}
