<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\DeleteVirtualAgentAction;

final class DeleteVirtualAgentMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->agent());
    }

    protected function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
        ];
    }

    protected function handle(array $validated): Response
    {
        app(DeleteVirtualAgentAction::class)->execute($this->agent());

        return Response::text('Virtual agent deleted.');
    }
}
