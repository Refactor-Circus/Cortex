<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ShowMcpInstructionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionResource;

final class ShowServerInstructionsMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->instruction());
    }

    protected function rules(): array
    {
        return $this->serverRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $instruction = app(ShowMcpInstructionAction::class)->execute($this->serverName());

        return (new McpInstructionResource($instruction))->toStructuredResponse();
    }
}
