<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy;

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
