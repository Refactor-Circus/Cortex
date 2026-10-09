<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Mcp\Requests;

use JayI\Cortex\Domains\RedirectDomain\Actions\DeleteRedirectDomainAction;
use Laravel\Mcp\Response;

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
