<?php

declare(strict_types=1);

namespace JayI\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Domains\Tool\Policies\ToolDescriptionPolicy;

/**
 * Each ability only for users granted "tool-descriptions.{ability}".
 */
final class GrantedToolDescriptionPolicy extends ToolDescriptionPolicy
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

    public function view(?Authenticatable $user, ToolDescriptionModel $description): bool
    {
        return $this->granted($user, 'view');
    }

    public function update(?Authenticatable $user, ToolDescriptionModel $description): bool
    {
        return $this->granted($user, 'update');
    }

    public function delete(?Authenticatable $user, ToolDescriptionModel $description): bool
    {
        return $this->granted($user, 'delete');
    }

    protected function prefix(): string
    {
        return 'tool-descriptions';
    }
}
