<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Policies\VirtualAgentPolicy;

/**
 * Each ability only for users granted "virtual-agents.{ability}".
 */
final class GrantedVirtualAgentPolicy extends VirtualAgentPolicy
{
    use GrantsAbilities;

    public function viewAny(?Authenticatable $user): bool
    {
        return $this->granted($user, 'viewAny');
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->granted($user, 'create');
    }

    public function view(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return $this->granted($user, 'view');
    }

    public function update(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return $this->granted($user, 'update');
    }

    public function delete(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return $this->granted($user, 'delete');
    }

    public function run(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return $this->granted($user, 'run');
    }

    protected function prefix(): string
    {
        return 'virtual-agents';
    }
}
