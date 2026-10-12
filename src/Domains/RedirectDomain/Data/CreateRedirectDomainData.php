<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Data;

use Closure;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateRedirectDomainAction: the origin, host or redirect URL to
 * allow, stored as its origin.
 */
final readonly class CreateRedirectDomainData extends Data
{
    public function __construct(
        public string $domain,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || RedirectDomains::normalize($value) === null) {
                    $fail(__('cortex::cortex.redirect_domain_invalid'));
                }
            }],
        ];
    }
}
