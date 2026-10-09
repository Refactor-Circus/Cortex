<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Support\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Foundation\Policies\Policy as FoundationPolicy;

/**
 * Shared checks for the bundled policies.
 *
 * Each policy is registered from `cortex.policies`, so an application swaps
 * one by pointing its model at another class there.
 *
 * Cortex records have no owner: prompts, agents and overrides are shared
 * configuration. The bundled policies therefore allow every ability, for
 * guests too, which keeps the JSON API and MCP tools behind your route
 * middleware exactly as before. Type the user parameter as non-nullable in
 * your own policy to require a signed-in user.
 *
 * The shared runtime's `allowsOn()` needs a signed-in user, so the bundled
 * policies keep `allowsOnParent()`, which also asks for guests.
 */
abstract class Policy extends FoundationPolicy
{
    /**
     * Ask the Gate about the parent record, so a version follows whichever
     * policy is registered for the prompt or override it belongs to.
     */
    protected function allowsOnParent(?Authenticatable $user, string $ability, Model $parent): bool
    {
        return Gate::forUser($user)->allows($ability, $parent);
    }
}
