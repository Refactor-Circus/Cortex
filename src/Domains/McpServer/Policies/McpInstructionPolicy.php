<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Support\Policies\Policy;

/**
 * Answers `$user->can(...)` for MCP server instruction overrides.
 *
 * Overrides have no owner, so every ability is allowed, for guests too: the route
 * middleware in front of the API and MCP server decides who gets in. Point
 * `cortex.policies` at your own class to restrict them.
 */
class McpInstructionPolicy extends Policy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return true;
    }

    public function delete(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return true;
    }
}
