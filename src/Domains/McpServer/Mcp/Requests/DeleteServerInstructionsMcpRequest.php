<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Cortex\Domains\McpServer\Actions\DeleteMcpInstructionAction;

final class DeleteServerInstructionsMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->instruction());
    }

    protected function rules(): array
    {
        return [
            'server' => ['required', 'string'],
        ];
    }

    protected function handle(array $validated): Response
    {
        app(DeleteMcpInstructionAction::class)->execute($this->instruction());

        return Response::text('Server instructions override deleted.');
    }
}
