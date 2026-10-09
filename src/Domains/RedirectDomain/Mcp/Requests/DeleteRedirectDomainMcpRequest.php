<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\DeleteRedirectDomainAction;

final class DeleteRedirectDomainMcpRequest extends RedirectDomainMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->domain());
    }

    protected function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            ...DeleteRedirectDomainAction::rules(),
        ];
    }

    protected function handle(array $validated): Response
    {
        app(DeleteRedirectDomainAction::class)->execute($this->domain());

        return Response::text('Redirect domain removed.');
    }
}
