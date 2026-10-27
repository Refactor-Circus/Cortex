<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;

/**
 * A host policy written before the ConcreteServer rename: it type-hints the
 * instructions model at its old name and is listed in `cortex.policies` under
 * that old name. Anyone may read server instructions; nobody may change them.
 */
final class DenyingOldKeyMcpInstructionPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return false;
    }

    public function view(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return false;
    }

    public function delete(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return false;
    }
}
