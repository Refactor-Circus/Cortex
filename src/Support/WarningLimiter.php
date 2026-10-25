<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rate limit for warnings about stored state that stays wrong until someone
 * fixes it (a stale or ambiguous tool entry, a corrupt value, a missing
 * column, a failing context rebuild): each logs once per server and subject
 * per `cortex.mcp.warning_ttl` window, across requests and nodes.
 *
 * The claim is one atomic `add()` on the publication cache's store, keyed by
 * {@see PublicationCache::warningKey()}. When the store throws the warning is
 * logged anyway, and nothing is ever thrown to the caller.
 */
final class WarningLimiter
{
    private const int DEFAULT_TTL = 3600;

    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * Log the warning unless one for the same server and subject was logged
     * within the window. Returns whether it was logged.
     *
     * @param  array<string, mixed>  $context
     */
    public function warn(string $server, string $subject, string $message, array $context = []): bool
    {
        if (! $this->claim($server, $subject)) {
            return false;
        }

        Log::warning($message, $context);

        return true;
    }

    private function claim(string $server, string $subject): bool
    {
        try {
            return $this->cache->store()->add($this->cache->warningKey($server, $subject), true, $this->ttl());
        } catch (Throwable) {
            // A cache that cannot rate-limit must not hide the warning.
            return true;
        }
    }

    /**
     * The window in seconds, at least one: `Cache::add()` refuses a zero or
     * negative TTL, which would silence the warning for good.
     */
    private function ttl(): int
    {
        return max(1, (int) (config('cortex.mcp.warning_ttl') ?? self::DEFAULT_TTL));
    }
}
