<?php

declare(strict_types=1);

namespace JayI\Cortex\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Models\VirtualAgent;

/**
 * Answers `$user->can(...)` for virtual agents.
 *
 * Virtual agents have no owner, so every ability is allowed, for guests too: the route
 * middleware in front of the API and MCP server decides who gets in. Point
 * `cortex.policies` at your own class to restrict them.
 */
class VirtualAgentPolicy extends Policy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return true;
    }

    public function delete(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return true;
    }

    /**
     * Running an agent calls its provider and tools.
     */
    public function run(?Authenticatable $user, VirtualAgent $agent): bool
    {
        return true;
    }
}
