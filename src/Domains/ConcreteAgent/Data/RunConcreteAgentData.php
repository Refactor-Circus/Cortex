<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for RunConcreteAgentAction: what to send the agent.
 */
final readonly class RunConcreteAgentData extends Data
{
    public function __construct(
        public string $input,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'input' => ['required', 'string'],
        ];
    }
}
