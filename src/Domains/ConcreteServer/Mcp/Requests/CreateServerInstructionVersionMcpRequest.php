<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionVersionResource;

final class CreateServerInstructionVersionMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('create', McpInstructionVersionModel::class, [$this->instructionOrNew()]);
    }

    protected function rules(): array
    {
        return $this->serverRules() + CreateMcpInstructionVersionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $version = app(CreateMcpInstructionVersionAction::class)->execute(
            $this->serverName(),
            CreateMcpInstructionVersionData::fromValidated($validated),
        );

        return (new McpInstructionVersionResource($version))->toStructuredResponse();
    }
}
