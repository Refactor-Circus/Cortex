<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests\Support;

use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Testing\TestListResponse;
use Laravel\Mcp\Server\Tool;
use ReflectionProperty;

/**
 * Reads what laravel/mcp keeps out of reach (D-025): the tools a
 * `ServerContext` was built with (its protected `$tools`) and the ordered
 * names in a test client's `tools/list` answer. The vendor pin test and the
 * order tests share it, so a laravel/mcp change breaks both in one place.
 */
final class ServerContextTools
{
    /**
     * The context's `$tools` exactly as it was built.
     *
     * @return array<int|string, mixed>
     */
    public static function of(ServerContext $context): array
    {
        /** @var array<int|string, mixed> */
        return (new ReflectionProperty(ServerContext::class, 'tools'))->getValue($context);
    }

    /**
     * The `name()` of each tool the context was built with, in order. A
     * class name is built through the container; a ToolSearch group is
     * listed by its key.
     *
     * @return list<string>
     */
    public static function names(ServerContext $context): array
    {
        $names = [];

        foreach (self::of($context) as $key => $tool) {
            if (is_array($tool)) {
                $names[] = (string) $key;

                continue;
            }

            $tool = is_string($tool) ? app($tool) : $tool;

            $names[] = $tool instanceof Tool ? $tool->name() : get_debug_type($tool);
        }

        return $names;
    }

    /**
     * The tool names of a `tools/list` answer, in the order the client got
     * them.
     *
     * @return list<string>
     */
    public static function listed(TestListResponse $response): array
    {
        /** @var list<string> */
        return (fn (): array => array_values(array_column($this->items, 'name')))->call($response);
    }
}
