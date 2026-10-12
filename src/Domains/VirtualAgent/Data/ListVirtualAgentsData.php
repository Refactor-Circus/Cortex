<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListVirtualAgentsAction: the page to show. `summary` is for
 * lists that show each agent's published version number only: the version
 * content and sub-agents are not loaded. It is never read from input.
 */
final readonly class ListVirtualAgentsData extends Data
{
    public function __construct(
        public int $perPage = 15,
        public ?int $page = null,
        public bool $summary = false,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return self::pagination()->rules();
    }
}
