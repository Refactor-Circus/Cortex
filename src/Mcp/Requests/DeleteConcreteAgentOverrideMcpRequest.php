<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use JayI\Cortex\Actions\DeleteConcreteAgentOverrideAction;
use Laravel\Mcp\Response;

final class DeleteConcreteAgentOverrideMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->override());
    }

    protected function rules(): array
    {
        return [
            'agent' => ['required', 'string'],
        ];
    }

    protected function handle(array $validated): Response
    {
        app(DeleteConcreteAgentOverrideAction::class)->execute($this->override());

        return Response::text('Concrete agent overrides deleted.');
    }
}
