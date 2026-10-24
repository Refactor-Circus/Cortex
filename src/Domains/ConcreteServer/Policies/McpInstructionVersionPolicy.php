<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Support\Policies\Policy;

/**
 * Versions belong to their override, so each check defers to it through the Gate:
 * reading a version needs `view` on the override, adding or publishing one needs
 * `update`. Versions are immutable, so nothing changes or deletes one.
 */
class McpInstructionVersionPolicy extends Policy
{
    public function viewAny(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return $this->allowsOn($user, 'view', $instruction);
    }

    public function view(?Authenticatable $user, McpInstructionVersionModel $version): bool
    {
        return $this->allowsOn($user, 'view', $version->mcpInstruction);
    }

    public function create(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return $this->allowsOn($user, 'update', $instruction);
    }

    public function publish(?Authenticatable $user, McpInstructionVersionModel $version): bool
    {
        return $this->allowsOn($user, 'update', $version->mcpInstruction);
    }
}
