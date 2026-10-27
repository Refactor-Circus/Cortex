<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;

/**
 * A host policy written before `updateTools` existed: it allows every
 * instruction ability, guests included, but defines no `updateTools`, so
 * the Gate's default denies changing or clearing a server's tool list.
 */
final class NoUpdateToolsMcpInstructionPolicy
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
