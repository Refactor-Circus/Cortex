<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy;

/**
 * Nobody may change a virtual agent, or add or publish its prompt versions.
 */
final class ReadOnlyVirtualAgentPolicy extends VirtualAgentPolicy
{
    public function update(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return false;
    }
}
