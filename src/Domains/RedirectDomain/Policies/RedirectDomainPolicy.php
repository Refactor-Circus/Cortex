<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Support\Policies\Policy;

/**
 * Answers `$user->can(...)` for redirect domains.
 *
 * A domain without an owner follows the rest of Cortex: every ability is
 * allowed, for guests too, and the route middleware in front of the API and
 * MCP server decides who gets in.
 *
 * A domain with an owner (an organization, a user) is that owner's to
 * manage, so `create` and `delete` ask the owner's own policy: a signed-in
 * user who may `update` the owner may add or remove its domains. Guests are
 * refused, and so is everyone for a domain whose owner row no longer exists.
 * The Atrium screens, the JSON API and the MCP tools all ask this policy, so
 * the rule holds on each of them. `viewAny` still allows everyone.
 *
 * Point `RedirectDomainModel` at your own class in `cortex.policies` to
 * decide differently; this one is then not asked at all.
 */
class RedirectDomainPolicy extends Policy
{
    public function viewAny(?Authenticatable $user, ?Model $owner = null): bool
    {
        return true;
    }

    public function create(?Authenticatable $user, ?Model $owner = null): bool
    {
        if ($owner === null) {
            return true;
        }

        return $this->mayUpdate($user, $owner);
    }

    public function delete(?Authenticatable $user, RedirectDomainModel $domain): bool
    {
        if ($domain->owner_type === null) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $this->mayUpdate($user, $domain->owner);
    }

    /**
     * Whether a signed-in user may update the owner, by the owner's own
     * policy. A missing owner is refused.
     */
    private function mayUpdate(?Authenticatable $user, ?Model $owner): bool
    {
        return $user !== null && $owner !== null && Gate::forUser($user)->allows('update', $owner);
    }
}
