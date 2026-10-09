<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use JayI\Cortex\Domains\RedirectDomain\Resources\RedirectDomainResource;

final class IndexRedirectDomainsRequest extends RedirectDomainRequest
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', RedirectDomainModel::class, [$this->owner()]);
    }

    public function rules(): array
    {
        return ListRedirectDomainsAction::rules();
    }

    public function persist(): JsonResponse
    {
        $domains = app(ListRedirectDomainsAction::class)->execute($this->owner());

        return RedirectDomainResource::collection($domains)->response();
    }
}
