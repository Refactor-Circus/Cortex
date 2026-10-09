<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy;

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

    public function view(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return $this->granted($user, 'view');
    }

    public function update(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return $this->granted($user, 'update');
    }

    public function delete(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return $this->granted($user, 'delete');
    }

    public function run(?Authenticatable $user, VirtualAgentModel $agent): bool
    {
        return $this->granted($user, 'run');
    }

    protected function prefix(): string
    {
        return 'virtual-agents';
    }
}
