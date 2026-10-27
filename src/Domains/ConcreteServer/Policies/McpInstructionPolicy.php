<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Support\Policies\Policy;

/**
 * Answers `$user->can(...)` for MCP server instruction overrides.
 *
 * Overrides have no owner, so every ability is allowed, for guests too: the route
 * middleware in front of the API and MCP server decides who gets in. Point
 * `cortex.policies` at your own class to restrict them.
 */
class McpInstructionPolicy extends Policy
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

    /**
     * Overriding or clearing the server's tool list (PUT and DELETE
     * `cortex/servers/{server}/tools`, the `update-server-tools` and
     * `clear-server-tools` MCP tools, the Atrium tool editor). Checked on the
     * server's override, or an unsaved one naming the server when it has
     * none.
     *
     * The bundled default allows it for guests too. This is the trust
     * boundary for tools: no per-tool ability is checked, so whoever passes
     * it can put any registered Cortex tool (`Laravel\Ai` tools included) on
     * the server, in front of every MCP client of that server. Outside the
     * `local` environment Cortex logs a warning at boot while guests are
     * allowed. A host policy without this method denies it (the Gate's
     * default). To allow signed-in users you trust only:
     *
     *     public function updateTools(?Authenticatable $user, McpInstructionModel $instruction): bool
     *     {
     *         if ($user === null) {
     *             return false;
     *         }
     *
     *         return $user->can('manage-mcp-tools');
     *     }
     */
    public function updateTools(?Authenticatable $user, McpInstructionModel $instruction): bool
    {
        return true;
    }
}
