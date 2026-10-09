<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy;

/**
 * Agents may be managed but never run.
 */
final class NoRunAgentPolicy extends VirtualAgentPolicy
{
    public function run(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return false;
    }
}
