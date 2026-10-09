<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Requests;

use Illuminate\Support\Arr;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Resources\RedirectDomainResource;

final class CreateRedirectDomainMcpRequest extends RedirectDomainMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('create', RedirectDomainModel::class, [$this->owner()]);
    }

    protected function rules(): array
    {
        return [
            ...CreateRedirectDomainAction::rules(),
            ...ListRedirectDomainsAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $domain = app(CreateRedirectDomainAction::class)->execute(
            Arr::except($validated, ['owner_type', 'owner_id']),
            $this->owner(),
        );

        return Response::structured((new RedirectDomainResource($domain))->resolve());
    }
}
