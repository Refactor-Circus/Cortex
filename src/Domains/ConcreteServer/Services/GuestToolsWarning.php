<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Services;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionPolicy;
use Throwable;

/**
 * Warns once per process, outside the `local` environment, when guests may
 * change a concrete MCP server's tool list (`updateTools`), which the
 * bundled policy allows: anyone who reaches the routes or MCP tools could
 * put any registered tool in front of that server's clients.
 *
 * Called from the provider's `booted` callback. It never runs in the
 * console, never throws and does no I/O of its own: the bundled policy is
 * recognised from `cortex.policies` without asking the Gate, and any other
 * policy is asked as a guest about an unsaved override per overridable
 * server, as the requests ask about the server's override. A policy that
 * throws logs nothing.
 */
final class GuestToolsWarning
{
    private const string ABILITY = 'updateTools';

    /**
     * Whether this process already ran the check.
     */
    private static bool $checked = false;

    public function __construct(
        private readonly Application $app,
        private readonly Repository $config,
        private readonly Gate $gate,
        private readonly McpServerRegistry $servers,
    ) {}

    public function check(): void
    {
        if (self::$checked) {
            return;
        }

        self::$checked = true;

        try {
            if ($this->app->runningInConsole() || $this->app->environment('local')) {
                return;
            }

            $servers = $this->serversOpenToGuests();

            if ($servers === []) {
                return;
            }

            Log::warning(sprintf(
                'Cortex lets guests change the tool list of MCP servers [%s]: the %s ability of the McpInstructionModel policy allows guests, so anyone who reaches the Cortex routes or MCP tools can put any registered tool on these servers. See the README section on overriding a concrete server\'s tools for a deny-by-default policy.',
                implode(', ', $servers),
                self::ABILITY,
            ), ['servers' => $servers]);
        } catch (Throwable) {
            // A boot-time diagnostic must never break the application.
        }
    }

    /**
     * Let the check run again in this process.
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$checked = false;
    }

    /**
     * The overridable servers whose tools guests may change.
     *
     * @return list<string>
     */
    private function serversOpenToGuests(): array
    {
        $servers = array_values(array_filter(
            $this->servers->names(),
            $this->servers->supportsToolOverrides(...),
        ));

        if ($this->config->get('cortex.policies.'.McpInstructionModel::class) === McpInstructionPolicy::class) {
            return $servers;
        }

        $guest = $this->gate->forUser(null);

        return array_values(array_filter(
            $servers,
            fn (string $server): bool => $guest->allows(self::ABILITY, new McpInstructionModel(['server' => $server])),
        ));
    }
}
