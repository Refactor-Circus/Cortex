<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\DeleteMcpInstructionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionResource;

final class DeleteServerInstructionsMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->instruction());
    }

    protected function rules(): array
    {
        return $this->serverRules();
    }

    /**
     * Text when the override row goes with its instructions; the row as
     * structured content when it survives because it still holds a tool
     * override (D-057).
     */
    protected function handle(array $validated): Response|ResponseFactory
    {
        $surviving = app(DeleteMcpInstructionAction::class)->execute($this->instruction());

        if ($surviving === null) {
            return Response::text('Server instructions override deleted.');
        }

        return (new McpInstructionResource($surviving))->toStructuredResponse();
    }
}
