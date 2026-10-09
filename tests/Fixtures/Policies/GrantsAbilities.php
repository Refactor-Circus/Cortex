<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Allows an ability only to a signed-in user whose `abilities` attribute
 * lists it as "{prefix}.{ability}", so a test grants exactly what it needs.
 */
trait GrantsAbilities
{
    abstract protected function prefix(): string;

    protected function granted(?Authenticatable $user, string $ability): bool
    {
        /** @var mixed $abilities */
        $abilities = $user === null ? [] : ($user->abilities ?? []);

        return is_array($abilities) && in_array($this->prefix().'.'.$ability, $abilities, true);
    }
}
