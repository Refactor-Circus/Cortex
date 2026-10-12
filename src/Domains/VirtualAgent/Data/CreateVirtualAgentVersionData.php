<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateVirtualAgentVersionAction: the new prompt, and whether to
 * publish it straight away.
 */
final readonly class CreateVirtualAgentVersionData extends Data
{
    public function __construct(
        public string $content,
        public bool $publish = false,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'publish' => ['sometimes', 'boolean'],
        ];
    }
}
