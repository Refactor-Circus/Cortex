<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Support;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\AgentTool;
use Laravel\Ai\Tools\ToolNameResolver;
use Laravel\Mcp\Server\Tool as McpTool;

/**
 * The name a model sees for an entry in an agent's toolset, resolved the
 * same way laravel/ai names it when the agent runs: agents through
 * AgentTool, AI tools through ToolNameResolver, MCP tools by their own name.
 */
final class ToolName
{
    public static function of(mixed $tool): string
    {
        return match (true) {
            $tool instanceof Agent => (new AgentTool($tool))->name(),
            $tool instanceof Tool => ToolNameResolver::resolve($tool),
            $tool instanceof McpTool => $tool->name(),
            is_object($tool) => class_basename($tool),
            default => (string) $tool,
        };
    }
}
