<?php

declare(strict_types=1);

namespace JayI\Cortex\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;

/**
 * Prompt versions belong to their virtual agent, so each check defers to it
 * through the Gate: reading a version needs `view` on the agent, adding or
 * publishing one needs `update`. Versions are immutable, so nothing changes
 * or deletes one.
 */
class VirtualAgentVersionPolicy extends Policy
{
    public function viewAny(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return $this->allowsOnParent($user, 'view', $agent);
    }

    public function view(?Authenticatable $user, VirtualAgentVersion $version): bool
    {
        return $this->allowsOnParent($user, 'view', $version->virtualAgent);
    }

    public function create(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return $this->allowsOnParent($user, 'update', $agent);
    }

    public function publish(?Authenticatable $user, VirtualAgentVersion $version): bool
    {
        return $this->allowsOnParent($user, 'update', $version->virtualAgent);
    }
}
