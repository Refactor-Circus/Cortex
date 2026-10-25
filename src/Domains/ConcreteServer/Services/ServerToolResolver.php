<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Services;

use Illuminate\Contracts\Container\Container;
use Laravel\Ai\Contracts\Tool as AiTool;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolDescriptionOverrides;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Support\AiToolAsMcpTool;
use RefactorCircus\Cortex\Support\WarningLimiter;
use Throwable;

/**
 * Turns a concrete server's stored tool list into the tools it serves.
 *
 * An entry names a tool the way validation does: one of the server's code
 * tools by its `name()` first, otherwise a tool registered with Cortex by
 * its registration name (MCP tools as they are, `Laravel\Ai` tools through
 * {@see AiToolAsMcpTool}). A code tool wins when both match (D-021).
 *
 * The code tools are the ones the server's own context was built with, so
 * tools a host adds in its constructor or `boot()` count; ToolSearch groups
 * are flattened, so an overridden server serves a flat list. Each tool keeps
 * its own gating: `ServerContext::tools()` still drops a tool that does not
 * register.
 */
final class ServerToolResolver
{
    public function __construct(
        private readonly Container $container,
        private readonly ToolRegistry $registry,
        private readonly ToolDescriptionOverrides $descriptions,
        private readonly WarningLimiter $warnings,
    ) {}

    /**
     * The tools to serve, in stored order. Entries that resolve to no tool,
     * whose tool throws while built (D-016, D-022) or that also name a
     * registered tool (D-021) are logged through the rate limit, once per
     * call; the context memo makes that once per request.
     *
     * @param  array<int|string, mixed>  $codeTools  the context's tools as declared
     * @param  list<string>  $entries
     * @return list<Tool>
     */
    public function serve(string $server, array $codeTools, array $entries): array
    {
        $resolved = $this->resolve($codeTools, $entries);

        foreach ($resolved['ambiguous'] as $entry) {
            $this->warnings->warn(
                $server,
                'ambiguous:'.$entry,
                sprintf(
                    'Cortex serves the code tool [%s] of MCP server [%s]: the tool override entry also names a registered Cortex tool, and the code tool wins.',
                    $entry,
                    $server,
                ),
                ['server' => $server, 'entry' => $entry],
            );
        }

        foreach ($resolved['missing'] as $entry) {
            if (array_key_exists($entry, $resolved['failed'])) {
                $this->warnings->warn(
                    $server,
                    'failed:'.$entry,
                    sprintf(
                        'Cortex skips the tool [%s] in the tool override of MCP server [%s]: building the tool failed.',
                        $entry,
                        $server,
                    ),
                    ['server' => $server, 'entry' => $entry, 'message' => $resolved['failed'][$entry]],
                );

                continue;
            }

            $this->warnings->warn(
                $server,
                'stale:'.$entry,
                sprintf(
                    'Cortex skips the tool [%s] in the tool override of MCP server [%s]: neither the server code nor the Cortex tool registry has a tool by that name.',
                    $entry,
                    $server,
                ),
                ['server' => $server, 'entry' => $entry],
            );
        }

        return $resolved['tools'];
    }

    /**
     * What the stored entries resolve to, without logging: the tools to
     * serve in stored order, the entries that resolve to nothing or whose
     * tool threw (`missing`, in stored order), the message of each throw
     * (`failed`) and the entries that name a code tool and a registered tool
     * (`ambiguous`).
     *
     * @param  array<int|string, mixed>  $codeTools  the context's tools as declared
     * @param  list<string>  $entries
     * @return array{tools: list<Tool>, missing: list<string>, failed: array<string, string>, ambiguous: list<string>}
     */
    public function resolve(array $codeTools, array $entries): array
    {
        $code = $this->codeTools($codeTools);
        $registered = $this->registeredClasses();

        $tools = [];
        $served = [];
        $missing = [];
        $failed = [];
        $ambiguous = [];

        foreach ($entries as $entry) {
            if (isset($code[$entry])) {
                $tool = $code[$entry];

                if (isset($registered[$entry])) {
                    $ambiguous[] = $entry;
                }
            } elseif (isset($registered[$entry])) {
                try {
                    $tool = $this->registeredTool($entry, $registered[$entry]);
                } catch (Throwable $exception) {
                    $missing[] = $entry;
                    $failed[$entry] = $exception->getMessage();

                    continue;
                }
            } else {
                $missing[] = $entry;

                continue;
            }

            // Validation keeps served names apart; a tool renamed since is
            // served once.
            if (isset($served[$tool->name()])) {
                continue;
            }

            $served[$tool->name()] = true;
            $tools[] = $tool;
        }

        return ['tools' => $tools, 'missing' => $missing, 'failed' => $failed, 'ambiguous' => $ambiguous];
    }

    /**
     * The server's code tools by `name()`, the first of a name winning.
     * ToolSearch groups are flattened; a class that cannot be built cannot
     * be named, so no entry resolves to it.
     *
     * @param  array<int|string, mixed>  $declared
     * @return array<string, Tool>
     */
    private function codeTools(array $declared): array
    {
        $tools = [];

        foreach ($declared as $entry) {
            foreach (is_array($entry) ? $entry : [$entry] as $tool) {
                try {
                    $tool = is_string($tool) ? $this->container->make($tool) : $tool;
                } catch (Throwable) {
                    continue;
                }

                if ($tool instanceof Tool) {
                    $tools[$tool->name()] ??= $tool;
                }
            }
        }

        return $tools;
    }

    /**
     * Every registered tool's class by registration name.
     *
     * @return array<string, class-string>
     */
    private function registeredClasses(): array
    {
        $classes = [];

        foreach ($this->registry->summaries() as $summary) {
            $classes[$summary['name']] = $summary['class'];
        }

        return $classes;
    }

    /**
     * @param  class-string  $class
     */
    private function registeredTool(string $name, string $class): Tool
    {
        $tool = $this->container->make($class);

        if ($tool instanceof Tool) {
            return $tool;
        }

        /** @var AiTool $tool */
        return new AiToolAsMcpTool($tool, $this->descriptions->for($name));
    }
}
