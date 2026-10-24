<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Concerns;

use Laravel\Mcp\Server\ServerContext;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpInstructionOverrides;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Support\Server;

/**
 * Serve the published Cortex instructions override, when one exists, in
 * place of the code-declared instructions. Falls back to whatever the
 * server declares in code (attribute or property) when no version is
 * published or the server is not registered with Cortex.
 *
 * Use directly on servers that cannot extend {@see Server}.
 */
trait HasVersionedInstructions
{
    public function createContext(): ServerContext
    {
        $context = parent::createContext();

        $name = app(McpServerRegistry::class)->nameFor(static::class);

        $override = $name === null ? null : app(McpInstructionOverrides::class)->for($name);

        if ($override !== null) {
            // ServerContext::$instructions is a public non-readonly property
            // in laravel/mcp; recheck this assignment on vendor upgrades.
            $context->instructions = $override;
        }

        return $context;
    }
}
