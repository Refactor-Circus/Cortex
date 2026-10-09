<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Policies\ToolDescriptionPolicy;

/**
 * No tool description may be overridden.
 */
final class FrozenToolDescriptionPolicy extends ToolDescriptionPolicy
{
    public function update(?Authenticatable $user, ToolDescriptionModel $description): bool
    {
        return false;
    }
}
