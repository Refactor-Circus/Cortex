<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * The policy of a model that owns redirect domains, standing in for a host's
 * own (an organization's or a user's). It answers `update` from a static
 * switch, for guests too, so a test proves the redirect-domain policy itself
 * refuses guests. Call `reset()` after each test.
 */
final class OwnerPolicyFixture
{
    public static bool $allowsUpdate = true;

    /**
     * How many times `update` was asked.
     */
    public static int $asked = 0;

    public static function reset(): void
    {
        self::$allowsUpdate = true;
        self::$asked = 0;
    }

    public function update(?Authenticatable $user, Model $owner): bool
    {
        self::$asked++;

        return self::$allowsUpdate;
    }
}
