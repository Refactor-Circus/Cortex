<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use JayI\Cortex\Domains\McpServer\Resources\McpInstructionVersionResource;

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
