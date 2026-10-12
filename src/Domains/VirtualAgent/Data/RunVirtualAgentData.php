<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for RunVirtualAgentAction: what to send the agent.
 */
final readonly class RunVirtualAgentData extends Data
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
