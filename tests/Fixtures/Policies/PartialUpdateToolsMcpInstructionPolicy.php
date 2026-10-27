<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;

/**
 * Lets anyone, guests included, change the tool list of the `echo` server
 * only; every other server's tools are refused. The server is read from the
 * override the ability is checked on, as the requests and the guest boot
 * warning pass it.
 */
final class PartialUpdateToolsMcpInstructionPolicy
{
    public const string ALLOWED_SERVER = 'echo';

    public function view(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return true;
    }

    public function updateTools(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return $instruction->server === self::ALLOWED_SERVER;
    }
}
