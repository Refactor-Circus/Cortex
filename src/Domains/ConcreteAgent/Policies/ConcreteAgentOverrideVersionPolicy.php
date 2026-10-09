<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Support\Policies\Policy;

/**
 * Versions belong to their override, so each check defers to it through the Gate:
 * reading a version needs `view` on the override, adding or publishing one needs
 * `update`. Versions are immutable, so nothing changes or deletes one.
 */
class ConcreteAgentOverrideVersionPolicy extends Policy
{
    public function viewAny(?Authenticatable $user, ConcreteAgentOverrideModel $override): bool
    {
        return $this->allowsOnParent($user, 'view', $override);
    }

    public function view(?Authenticatable $user, ConcreteAgentOverrideVersionModel $version): bool
    {
        return $this->allowsOnParent($user, 'view', $version->concreteAgentOverride);
    }

    public function create(?Authenticatable $user, ConcreteAgentOverrideModel $override): bool
    {
        return $this->allowsOnParent($user, 'update', $override);
    }

    public function publish(?Authenticatable $user, ConcreteAgentOverrideVersionModel $version): bool
    {
        return $this->allowsOnParent($user, 'update', $version->concreteAgentOverride);
    }
}
