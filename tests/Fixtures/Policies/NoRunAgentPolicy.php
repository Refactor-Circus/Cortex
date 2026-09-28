<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Policies\VirtualAgentPolicy;

/**
 * Agents may be managed but never run.
 */
final class NoRunAgentPolicy extends VirtualAgentPolicy
{
    public function run(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return false;
    }
}
