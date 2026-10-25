<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool as AiTool;
use Laravel\Ai\Tools\Request as AiRequest;
use Laravel\Ai\Tools\ToolNameResolver;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Serves a registered `Laravel\Ai` tool over MCP, the inverse of laravel/ai's
 * `McpServerTool`: a concrete server's tool override can select any
 * registered Cortex tool, and MCP clients only call MCP tools.
 *
 * The tool keeps its own name (as laravel/ai names it), description (or its
 * published Cortex override), schema and gating: a `shouldRegister()` on the
 * wrapped tool is called through the container, so it can take the MCP
 * request and its user, and decides whether clients see it.
 *
 * @internal
 */
final class AiToolAsMcpTool extends Tool
{
    public function __construct(
        private readonly AiTool $tool,
        private readonly ?string $publishedDescription = null,
    ) {}

    public function tool(): AiTool
    {
        return $this->tool;
    }

    public function name(): string
    {
        return ToolNameResolver::resolve($this->tool);
    }

    public function title(): string
    {
        return Str::headline($this->name());
    }

    public function description(): string
    {
        return $this->publishedDescription ?? (string) $this->tool->description();
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->tool->schema($schema);
    }

    public function shouldRegister(): bool
    {
        if (! method_exists($this->tool, 'shouldRegister')) {
            return true;
        }

        return (bool) Container::getInstance()->call([$this->tool, 'shouldRegister']);
    }

    public function handle(Request $request): Response
    {
        return Response::text((string) $this->tool->handle(new AiRequest($request->all())));
    }
}
