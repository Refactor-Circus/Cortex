<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
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
        return $this->ownerRules() + CreateRedirectDomainData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $domain = app(CreateRedirectDomainAction::class)->execute(
            CreateRedirectDomainData::fromValidated($validated),
            $this->owner(),
        );

        return (new RedirectDomainResource($domain))->toStructuredResponse();
    }
}
