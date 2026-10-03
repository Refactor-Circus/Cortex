<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Policies\McpInstructionPolicy;

/**
 * Each ability only for users granted "server-instructions.{ability}".
 */
final class GrantedMcpInstructionPolicy extends McpInstructionPolicy
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

    public function view(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return $this->granted($user, 'view');
    }

    public function update(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return $this->granted($user, 'update');
    }

    public function delete(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return $this->granted($user, 'delete');
    }

    protected function prefix(): string
    {
        return 'server-instructions';
    }
}
