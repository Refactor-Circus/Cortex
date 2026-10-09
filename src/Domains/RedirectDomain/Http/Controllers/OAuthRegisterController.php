<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JayI\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController as BaseOAuthRegisterController;

/**
 * laravel/mcp's dynamic client registration, allowing the stored redirect
 * domains beside `mcp.redirect_domains`.
 *
 * The base controller reads that config key in several places (the `*`
 * wildcard, localhost, the prefix match), so the stored domains are merged
 * into it for this one registration and the configured list is put back
 * afterwards, which keeps long-lived workers from accumulating domains that
 * have since been removed.
 */
final class OAuthRegisterController extends BaseOAuthRegisterController
{
    public function __invoke(Request $request): JsonResponse
    {
        $configured = config('mcp.redirect_domains', []);

        config(['mcp.redirect_domains' => app(RedirectDomains::class)->allowed()]);

        try {
            return parent::__invoke($request);
        } finally {
            config(['mcp.redirect_domains' => $configured]);
        }
    }
}
