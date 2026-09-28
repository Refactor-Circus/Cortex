<?php

declare(strict_types=1);

namespace JayI\Cortex\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Models\ConcreteAgentOverride;

/**
 * Answers `$user->can(...)` for concrete agent overrides.
 *
 * Overrides have no owner, so every ability is allowed, for guests too: the route
 * middleware in front of the API and MCP server decides who gets in. Point
 * `cortex.policies` at your own class to restrict them.
 */
class ConcreteAgentOverridePolicy extends Policy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, ConcreteAgentOverride $override): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, ConcreteAgentOverride $override): bool
    {
        return true;
    }

    public function delete(?Authenticatable $user, ConcreteAgentOverride $override): bool
    {
        return true;
    }

    /**
     * Running a concrete agent calls its provider and tools. Checked against
     * the agent's override, or an unsaved one when it has none.
     */
    public function run(?Authenticatable $user, ConcreteAgentOverride $override): bool
    {
        return true;
    }
}
