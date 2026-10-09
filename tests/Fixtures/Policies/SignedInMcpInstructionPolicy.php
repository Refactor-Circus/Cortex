<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;

/**
 * Server instructions are for signed-in users only: the user parameter is not
 * nullable, so the Gate denies guests without calling the method.
 */
final class SignedInMcpInstructionPolicy
{
    public function view(Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return true;
    }

    public function update(Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return true;
    }
}
