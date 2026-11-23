<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for SearchAgentsAction: the text agent names (and virtual agent
 * slugs) must contain. It arrives as the `q` input.
 */
final readonly class SearchAgentsData extends Data
{
    public function __construct(
        public string $query,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * The query is read from the `q` input.
     *
     * @param  array<array-key, mixed>  $validated
     * @return array<array-key, mixed>
     */
    protected static function prepare(array $validated): array
    {
        if (array_key_exists('q', $validated)) {
            $validated['query'] = $validated['q'];
        }

        return $validated;
    }
}
