<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverridePolicy;

/**
 * Each ability only for users granted "concrete-agents.{ability}".
 */
final class GrantedConcreteAgentOverridePolicy extends ConcreteAgentOverridePolicy
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

    public function view(?Authenticatable $user, ConcreteAgentOverrideModel $override): bool
    {
        return $this->granted($user, 'view');
    }

    public function update(?Authenticatable $user, ConcreteAgentOverrideModel $override): bool
    {
        return $this->granted($user, 'update');
    }

    public function delete(?Authenticatable $user, ConcreteAgentOverrideModel $override): bool
    {
        return $this->granted($user, 'delete');
    }

    public function run(?Authenticatable $user, ConcreteAgentOverrideModel $override): bool
    {
        return $this->granted($user, 'run');
    }

    protected function prefix(): string
    {
        return 'concrete-agents';
    }
}
