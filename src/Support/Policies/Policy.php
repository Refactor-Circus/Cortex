<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Support\Policies;

use RefactorCircus\Keystone\Policies\Policy as KeystonePolicy;

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
 * your own policy to require a signed-in user. A version follows its parent
 * through the shared runtime's `allowsOn()`, guests included.
 */
abstract class Policy extends KeystonePolicy
{
    //
}
