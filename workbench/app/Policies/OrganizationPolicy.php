<?php

declare(strict_types=1);

namespace Workbench\App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Workbench\App\Models\Organization;

/**
 * The host's own policy for its Organization, which Cortex's bundled
 * RedirectDomainPolicy asks `update` of for an organization's domains.
 * Anyone may view one; any signed-in user may update one until a test sets
 * `$deniesUpdate` (the test case resets it after every test).
 */
final class OrganizationPolicy
{
    public static bool $deniesUpdate = false;

    public function view(?Authenticatable $user, Organization $organization): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, Organization $organization): bool
    {
        return $user !== null && ! self::$deniesUpdate;
    }
}
