<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Concerns;

use Laravel\Mcp\Server\ServerContext;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpInstructionOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpToolOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerContextMemo;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerContextRebuilder;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\ServerToolResolver;
use RefactorCircus\Cortex\Domains\ConcreteServer\Support\Server;
use RefactorCircus\Cortex\Support\WarningLimiter;
use Throwable;

/**
 * Serve what Cortex overrides on a server registered with it:
 *
 * - the published instructions override, in place of the code-declared
 *   instructions (attribute or property), when a version is published;
 * - the stored tool override, in place of the code-declared tools, when the
 *   server's override row holds a tool list (the `cortex` server excepted).
 *   The list is served in stored order, as a flat list; see
 *   {@see ServerToolResolver} for how entries resolve.
 *
 * The context is built once per request ({@see ServerContextMemo}). When the
 * tool override cannot be applied to it (a laravel/mcp change, or a failure
 * in the rebuild), the code tools are served with the instructions override
 * still applied, and a rate-limited warning says why (D-028, D-047).
 *
 * Use directly on servers that cannot extend {@see Server}.
 */
trait HasCortexOverrides
{
    public function createContext(): ServerContext
    {
        $servers = app(McpServerRegistry::class);
        $name = $servers->nameFor(static::class);

        if ($name === null) {
            return parent::createContext();
        }

        return app(ServerContextMemo::class)->remember(static::class, function () use ($servers, $name): ServerContext {
            $context = parent::createContext();

            $instructions = app(McpInstructionOverrides::class)->for($name);

            if ($instructions !== null) {
                // ServerContext::$instructions is a public non-readonly property
                // in laravel/mcp; recheck this assignment on vendor upgrades.
                $context->instructions = $instructions;
            }

            $tools = $servers->supportsToolOverrides($name) ? app(McpToolOverrides::class)->for($name) : null;

            if ($tools === null) {
                return $context;
            }

            try {
                $rebuilder = app(ServerContextRebuilder::class);

                return $rebuilder->withTools(
                    $context,
                    app(ServerToolResolver::class)->serve($name, $rebuilder->tools($context), $tools),
                );
            } catch (Throwable $exception) {
                app(WarningLimiter::class)->warn(
                    $name,
                    'rebuild:'.$exception::class,
                    sprintf(
                        'Cortex serves the code tools of MCP server [%s]: applying its tool override failed with %s.',
                        $name,
                        $exception::class,
                    ),
                    ['server' => $name, 'message' => $exception->getMessage()],
                );

                return $context;
            }
        });
    }
}
