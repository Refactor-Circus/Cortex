<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium;

/**
 * Atrium status-dot variants for the states Cortex screens show, in one place
 * so every screen colours a state the same way.
 */
final class Badges
{
    /**
     * The Atrium colour of a state. `info` is kept for pending - awaiting
     * someone's decision - and Cortex has no such state.
     *
     * @param  'published'|'overridden'|'enabled'|'locked'|'from_code'|'disabled'  $status
     */
    public static function forStatus(string $status): string
    {
        return match ($status) {
            'published', 'overridden', 'enabled' => 'success',
            'locked' => 'warning',
            'from_code', 'disabled' => 'neutral',
        };
    }
}
