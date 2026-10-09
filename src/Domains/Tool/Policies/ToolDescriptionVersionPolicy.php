<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Support\Policies\Policy;

/**
 * Versions belong to their override, so each check defers to it through the Gate:
 * reading a version needs `view` on the override, adding or publishing one needs
 * `update`. Versions are immutable, so nothing changes or deletes one.
 */
class ToolDescriptionVersionPolicy extends Policy
{
    public function viewAny(?Authenticatable $user, ToolDescriptionModel $description): bool
    {
        return $this->allowsOnParent($user, 'view', $description);
    }

    public function view(?Authenticatable $user, ToolDescriptionVersionModel $version): bool
    {
        return $this->allowsOnParent($user, 'view', $version->toolDescription);
    }

    public function create(?Authenticatable $user, ToolDescriptionModel $description): bool
    {
        return $this->allowsOnParent($user, 'update', $description);
    }

    public function publish(?Authenticatable $user, ToolDescriptionVersionModel $version): bool
    {
        return $this->allowsOnParent($user, 'update', $version->toolDescription);
    }
}
