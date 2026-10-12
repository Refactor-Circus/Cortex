<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Resources\RedirectDomainResource;

final class StoreRedirectDomainRequest extends RedirectDomainRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', RedirectDomainModel::class, [$this->owner()]);
    }

    public function rules(): array
    {
        return $this->ownerRules() + CreateRedirectDomainData::rules();
    }

    public function persist(): JsonResponse
    {
        $domain = app(CreateRedirectDomainAction::class)->execute(
            CreateRedirectDomainData::fromValidated($this->validated()),
            $this->owner(),
        );

        return (new RedirectDomainResource($domain))->response()->setStatusCode(201);
    }
}
