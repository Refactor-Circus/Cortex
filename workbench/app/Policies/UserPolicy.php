<?php

declare(strict_types=1);

namespace Workbench\App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Workbench\App\Models\User;

/**
 * The host's own policy for its User, which Cortex's bundled
 * RedirectDomainPolicy asks `update` of for a user's domains. Anyone may view
 * one; any signed-in user may update one until a test sets `$deniesUpdate`
 * (the test case resets it after every test).
 */
final class UserPolicy
{
    public static bool $deniesUpdate = false;

    public function view(?Authenticatable $user, User $model): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, User $model): bool
    {
        return $user !== null && ! self::$deniesUpdate;
    }
}
