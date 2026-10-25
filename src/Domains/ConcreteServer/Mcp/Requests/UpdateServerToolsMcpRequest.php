<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionResource;

final class UpdateServerToolsMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('updateTools', $this->instructionOrNew());
    }

    protected function rules(): array
    {
        return $this->serverRules() + UpdateConcreteServerToolsData::rules($this->serverName());
    }

    protected function handle(array $validated): ResponseFactory
    {
        $instruction = app(UpdateConcreteServerToolsAction::class)->execute(
            $this->serverName(),
            UpdateConcreteServerToolsData::fromValidated($validated),
        );

        return (new McpInstructionResource($instruction))->toStructuredResponse();
    }
}
