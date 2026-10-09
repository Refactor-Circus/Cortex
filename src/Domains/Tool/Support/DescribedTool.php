<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Support;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Laravel\Ai\Tools\ToolNameResolver;
use Stringable;

/**
 * Decorates a tool with a published description override while delegating
 * everything else to the underlying tool.
 */
final class DescribedTool implements Tool
{
    public function __construct(
        private readonly Tool $tool,
        private readonly string $description,
    ) {}

    public function name(): string
    {
        return ToolNameResolver::resolve($this->tool);
    }

    public function description(): string
    {
        return $this->description;
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->tool->handle($request);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->tool->schema($schema);
    }
}
