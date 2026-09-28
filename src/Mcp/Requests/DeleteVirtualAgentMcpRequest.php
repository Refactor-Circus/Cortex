<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use JayI\Cortex\Actions\DeleteVirtualAgentAction;
use Laravel\Mcp\Response;

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
