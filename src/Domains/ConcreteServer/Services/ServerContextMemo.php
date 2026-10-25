<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Services;

use Closure;
use Laravel\Mcp\Server\ServerContext;

/**
 * The server contexts built in this request, by server class (D-026). A
 * concrete server builds its context with Cortex's overrides once per
 * request and reuses it, so every stale, failing, ambiguous or corrupt
 * entry logs at most once per client request.
 *
 * Bound as a scoped singleton: the memo ends with the request (Octane) or
 * the job (queue workers), so the next request sees a changed override.
 */
final class ServerContextMemo
{
    /**
     * @var array<string, ServerContext>
     */
    private array $contexts = [];

    /**
     * The context built for the server class in this request, building it
     * on the first call.
     *
     * @param  Closure(): ServerContext  $build
     */
    public function remember(string $server, Closure $build): ServerContext
    {
        return $this->contexts[$server] ??= $build();
    }
}
