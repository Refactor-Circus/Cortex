<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Mcp\Requests;

use JayI\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use JayI\Cortex\Domains\RedirectDomain\Resources\RedirectDomainResource;
use Laravel\Mcp\ResponseFactory;

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
