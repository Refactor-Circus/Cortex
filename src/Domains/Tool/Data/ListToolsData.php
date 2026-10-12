<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ListToolsAction: the tag to filter by, or null for every tool.
 */
final readonly class ListToolsData extends Data
{
    public function __construct(
        public ?string $tag = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'tag' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
