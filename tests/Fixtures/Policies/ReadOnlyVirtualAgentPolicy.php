<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Policies\VirtualAgentPolicy;

/**
 * Nobody may change a virtual agent, or add or publish its prompt versions.
 */
final class ReadOnlyVirtualAgentPolicy extends VirtualAgentPolicy
{
    public function update(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return false;
    }
}
