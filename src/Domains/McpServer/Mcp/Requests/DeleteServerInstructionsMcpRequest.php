<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Mcp\Requests;

use JayI\Cortex\Domains\McpServer\Actions\DeleteMcpInstructionAction;
use Laravel\Mcp\Response;

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
