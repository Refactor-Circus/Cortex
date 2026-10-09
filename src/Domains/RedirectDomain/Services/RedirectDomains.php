<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Support\PublicationCache;

/**
 * The origins MCP clients may register OAuth redirect URIs on: those in
 * `mcp.redirect_domains` plus every stored one, whoever owns it.
 */
final class RedirectDomains
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * Whether stored domains count when a client registers.
     */
    public function enabled(): bool
    {
        return (bool) config('cortex.redirect_domains.enabled', true);
    }

    /**
     * The configured domains followed by the stored ones.
     *
     * @return list<string>
     */
    public function allowed(): array
    {
        /** @var array<int, string> $configured */
        $configured = (array) config('mcp.redirect_domains', []);

        if (! $this->enabled()) {
            return array_values($configured);
        }

        return array_values(array_unique([...$configured, ...$this->stored()]));
    }

    /**
     * Every stored domain, once each.
     *
     * @return list<string>
     */
    public function stored(): array
    {
        /** @var list<string> */
        return $this->cache->remember($this->cache->redirectDomainsKey(), fn (): array => RedirectDomainModel::query()
            ->distinct()
            ->orderBy('domain')
            ->pluck('domain')
            ->all());
    }

    public function forget(): void
    {
        $this->cache->forget($this->cache->redirectDomainsKey());
    }

    /**
     * The owner a request names by morph alias (or class) and key, or null
     * when it names none. Anything that is not a model is not found.
     */
    public function owner(?string $type, int|string|null $id): ?Model
    {
        if ($type === null || $type === '') {
            return null;
        }

        $class = Relation::getMorphedModel($type) ?? $type;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            throw (new ModelNotFoundException)->setModel(Model::class, [$type]);
        }

        return $class::query()->findOrFail($id);
    }

    /**
     * The origin a domain or URL stands for: `claude.ai`,
     * `https://claude.ai/` and `https://claude.ai/api/mcp/auth_callback` are
     * all `https://claude.ai`. A bare host is taken as https. Null when the
     * value is no http(s) origin, so `*` can only come from config.
     */
    public static function normalize(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (! str_contains($value, '://')) {
            $value = 'https://'.$value;
        }

        $parts = parse_url($value);

        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);

        if (! in_array($scheme, ['http', 'https'], true)
            || preg_match('/^(\[[0-9a-f:.]+\]|[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*)$/', $host) !== 1) {
            return null;
        }

        return $scheme.'://'.$host.(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
