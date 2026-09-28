<?php

declare(strict_types=1);

namespace JayI\Cortex\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

/**
 * Versions belong to their override, so each check defers to it through the Gate:
 * reading a version needs `view` on the override, adding or publishing one needs
 * `update`. Versions are immutable, so nothing changes or deletes one.
 */
class ConcreteAgentOverrideVersionPolicy extends Policy
{
    public function viewAny(?Authenticatable $user, ConcreteAgentOverride $override): bool
    {
        return $this->allowsOnParent($user, 'view', $override);
    }

    public function view(?Authenticatable $user, ConcreteAgentOverrideVersion $version): bool
    {
        return $this->allowsOnParent($user, 'view', $version->concreteAgentOverride);
    }

    public function create(?Authenticatable $user, ConcreteAgentOverride $override): bool
    {
        return $this->allowsOnParent($user, 'update', $override);
    }

    public function publish(?Authenticatable $user, ConcreteAgentOverrideVersion $version): bool
    {
        return $this->allowsOnParent($user, 'update', $version->concreteAgentOverride);
    }
}
