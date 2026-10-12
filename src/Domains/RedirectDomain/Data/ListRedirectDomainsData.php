<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Options for ListRedirectDomainsAction, set by code and never read from
 * input: `source` keeps only global (`global`) or owned (`owned`) domains,
 * and `paged` answers one page of `perPage` domains, with their owners
 * loaded, instead of every stored domain.
 */
final readonly class ListRedirectDomainsData extends Data
{
    public function __construct(
        public ?string $source = null,
        public bool $paged = false,
        public int $perPage = 25,
        public ?int $page = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }
}
