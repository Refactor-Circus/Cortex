<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateConcreteAgentVersionAction: the new prompt for the agent's override, and
 * whether to publish it straight away.
 */
final readonly class CreateConcreteAgentVersionData extends Data
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
