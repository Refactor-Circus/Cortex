<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Cortex\Domains\RedirectDomain\Http\Requests\DeleteRedirectDomainRequest;
use JayI\Cortex\Domains\RedirectDomain\Http\Requests\IndexRedirectDomainsRequest;
use JayI\Cortex\Domains\RedirectDomain\Http\Requests\StoreRedirectDomainRequest;

final class RedirectDomainController
{
    public function index(IndexRedirectDomainsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreRedirectDomainRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DeleteRedirectDomainRequest $request, string $domain): Response
    {
        return $request->persist();
    }
}
