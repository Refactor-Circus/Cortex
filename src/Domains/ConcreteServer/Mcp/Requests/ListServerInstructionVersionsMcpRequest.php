<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ListMcpInstructionVersionsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionVersionResource;

final class ListServerInstructionVersionsMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', McpInstructionVersionModel::class, [$this->instruction()]);
    }

    protected function rules(): array
    {
        return $this->serverRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $versions = app(ListMcpInstructionVersionsAction::class)->execute($this->instruction());

        return McpInstructionVersionResource::collection($versions)->toStructuredResponse();
    }
}
