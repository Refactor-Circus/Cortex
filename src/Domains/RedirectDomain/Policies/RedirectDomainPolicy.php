<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Support\Policies\Policy;

/**
 * Answers `$user->can(...)` for redirect domains.
 *
 * Like the rest of Cortex every ability is allowed, for guests too: the
 * route middleware in front of the API and MCP server decides who gets in.
 * `create` receives the owner the domain is for (null for every client), so
 * your own class in `cortex.policies` can tell an organization's admins from
 * everyone else.
 */
class RedirectDomainPolicy extends Policy
{
    public function viewAny(?Authenticatable $user, ?Model $owner = null): bool
    {
        return true;
    }

    public function create(?Authenticatable $user, ?Model $owner = null): bool
    {
        return true;
    }

    public function delete(?Authenticatable $user, RedirectDomainModel $domain): bool
    {
        return true;
    }
}
