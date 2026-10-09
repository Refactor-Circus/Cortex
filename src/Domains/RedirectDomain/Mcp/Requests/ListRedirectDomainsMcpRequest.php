<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Resources\RedirectDomainResource;

final class ListRedirectDomainsMcpRequest extends RedirectDomainMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', RedirectDomainModel::class, [$this->owner()]);
    }

    protected function rules(): array
    {
        return ListRedirectDomainsAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $domains = app(ListRedirectDomainsAction::class)->execute($this->owner());

        return $this->structuredCollection(RedirectDomainResource::collection($domains)->resolve());
    }
}
