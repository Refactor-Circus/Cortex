<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Support\Policies\Policy;

/**
 * Prompt versions belong to their virtual agent, so each check defers to it
 * through the Gate: reading a version needs `view` on the agent, adding or
 * publishing one needs `update`. Versions are immutable, so nothing changes
 * or deletes one.
 */
class VirtualAgentVersionPolicy extends Policy
{
    public function viewAny(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return $this->allowsOnParent($user, 'view', $agent);
    }

    public function view(?Authenticatable $user, VirtualAgentVersionModel $version): bool
    {
        return $this->allowsOnParent($user, 'view', $version->virtualAgent);
    }

    public function create(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return $this->allowsOnParent($user, 'update', $agent);
    }

    public function publish(?Authenticatable $user, VirtualAgentVersionModel $version): bool
    {
        return $this->allowsOnParent($user, 'update', $version->virtualAgent);
    }
}
