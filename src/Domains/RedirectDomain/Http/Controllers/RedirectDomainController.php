<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Cortex\Domains\RedirectDomain\Http\Requests\DeleteRedirectDomainRequest;
use RefactorCircus\Cortex\Domains\RedirectDomain\Http\Requests\IndexRedirectDomainsRequest;
use RefactorCircus\Cortex\Domains\RedirectDomain\Http\Requests\StoreRedirectDomainRequest;

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
