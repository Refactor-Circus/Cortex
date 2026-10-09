<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use JayI\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use JayI\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use JayI\Cortex\Domains\RedirectDomain\Resources\RedirectDomainResource;

final class StoreRedirectDomainRequest extends RedirectDomainRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', RedirectDomainModel::class, [$this->owner()]);
    }

    public function rules(): array
    {
        return [
            ...CreateRedirectDomainAction::rules(),
            ...ListRedirectDomainsAction::rules(),
        ];
    }

    public function persist(): JsonResponse
    {
        $domain = app(CreateRedirectDomainAction::class)->execute(
            Arr::except($this->validated(), ['owner_type', 'owner_id']),
            $this->owner(),
        );

        return (new RedirectDomainResource($domain))->response()->setStatusCode(201);
    }
}
