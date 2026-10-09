<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Support;

use Closure;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Cache for published content (virtual agent prompts, tool description,
 * MCP instruction and concrete agent overrides, and the stored OAuth redirect
 * domains) that only changes when a new version is published or a domain is
 * added or removed — those actions invalidate explicitly.
 *
 * When Redis is available the Redis store is used with the flexible
 * (stale-while-revalidate) strategy, serving cached content instantly and
 * refreshing in the background; otherwise the default store caches until
 * the next explicit invalidation. Set `cortex.cache.store` to pin a store.
 */
final class PublicationCache
{
    /**
     * Whether the Redis cache store is usable, probed once per process.
     */
    private static ?bool $redisAvailable = null;

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $key, Closure $callback): mixed
    {
        if (! $this->enabled()) {
            return $callback();
        }

        $repository = $this->repository();

        try {
            if ($repository->getStore() instanceof RedisStore) {
                return $repository->flexible(
                    $key,
                    [
                        (int) config('cortex.cache.fresh', 300),
                        (int) config('cortex.cache.stale', 86400),
                    ],
                    $callback,
                );
            }

            return $repository->rememberForever($key, $callback);
        } catch (Throwable $exception) {
            report($exception);

            return $callback();
        }
    }

    public function forget(string ...$keys): void
    {
        if (! $this->enabled()) {
            return;
        }

        foreach ($keys as $key) {
            $this->repository()->forget($key);
        }
    }

    private function enabled(): bool
    {
        return (bool) config('cortex.cache.enabled', true);
    }

    /**
     * The `{cortex.published}` hash tag keeps each key and the
     * `illuminate:cache:flexible:created:` twin that flexible() fetches
     * alongside it in the same MGET on the same Redis cluster slot —
     * without it, clustered Redis rejects the cross-slot MGET and
     * phpredis returns false.
     */
    public function toolDescriptionsKey(): string
    {
        return '{cortex.published}.tool-descriptions';
    }

    public function mcpInstructionsKey(): string
    {
        return '{cortex.published}.mcp-instructions';
    }

    public function redirectDomainsKey(): string
    {
        return '{cortex.published}.redirect-domains';
    }

    public function concreteAgentsKey(): string
    {
        return '{cortex.published}.concrete-agents';
    }

    public function virtualAgentKey(string $agentId): string
    {
        return '{cortex.published}.virtual-agent.'.$agentId;
    }

    private function repository(): Repository
    {
        /** @var string|null $store */
        $store = config('cortex.cache.store');

        if ($store !== null) {
            return Cache::store($store);
        }

        return $this->redisAvailable() ? Cache::store('redis') : Cache::store();
    }

    private function redisAvailable(): bool
    {
        if (self::$redisAvailable !== null) {
            return self::$redisAvailable;
        }

        if (config('cache.stores.redis') === null) {
            return self::$redisAvailable = false;
        }

        try {
            $store = Cache::store('redis')->getStore();

            if (! $store instanceof RedisStore) {
                return self::$redisAvailable = false;
            }

            $store->connection()->ping();

            return self::$redisAvailable = true;
        } catch (Throwable) {
            return self::$redisAvailable = false;
        }
    }
}
