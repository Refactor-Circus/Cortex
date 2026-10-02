<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\McpInstruction;
use JayI\Cortex\Models\ToolDescription;

/**
 * Whether the signed-in user may use an ability, asked the way the JSON API
 * and MCP tools ask it: the same ability against the same subject, through
 * the policies in `cortex.policies`. Controllers refuse with it and views
 * hide controls with it (as `@cortexCan`), so a control is shown exactly
 * when its action is allowed.
 */
final class ScreenAccess
{
    /**
     * @param  Model|class-string<Model>  $subject
     * @param  array<int, mixed>  $arguments
     */
    public static function allows(string $ability, Model|string $subject, array $arguments = []): bool
    {
        return self::allowsFor(request()->user(), $ability, $subject, $arguments);
    }

    /**
     * The same check for a given user, or a guest.
     *
     * @param  Model|class-string<Model>  $subject
     * @param  array<int, mixed>  $arguments
     */
    public static function allowsFor(mixed $user, string $ability, Model|string $subject, array $arguments = []): bool
    {
        return Gate::forUser($user)->allows($ability, [$subject, ...$arguments]);
    }

    /**
     * A concrete agent's override, or an unsaved one when it has none: what
     * the API checks an agent against. Pass the override when it has already
     * been looked up.
     */
    public static function concreteAgent(string $agent, ?ConcreteAgentOverride $override = null): ConcreteAgentOverride
    {
        return $override ?? new ConcreteAgentOverride(['agent' => $agent]);
    }

    /**
     * A tool's description override, or an unsaved one when it has none.
     */
    public static function toolDescription(string $tool, ?ToolDescription $description = null): ToolDescription
    {
        return $description ?? new ToolDescription(['tool' => $tool]);
    }

    /**
     * An MCP server's instruction override, or an unsaved one when it has none.
     */
    public static function serverInstruction(string $server, ?McpInstruction $instruction = null): McpInstruction
    {
        return $instruction ?? new McpInstruction(['server' => $server]);
    }
}
