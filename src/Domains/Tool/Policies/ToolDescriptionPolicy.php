<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Support\Policies\Policy;

/**
 * Answers `$user->can(...)` for tool description overrides.
 *
 * Overrides have no owner, so every ability is allowed, for guests too: the route
 * middleware in front of the API and MCP server decides who gets in. Point
 * `cortex.policies` at your own class to restrict them.
 */
class ToolDescriptionPolicy extends Policy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, ToolDescriptionModel $description): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, ToolDescriptionModel $description): bool
    {
        return true;
    }

    public function delete(?Authenticatable $user, ToolDescriptionModel $description): bool
    {
        return true;
    }
}
