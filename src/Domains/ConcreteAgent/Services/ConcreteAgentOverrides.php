<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Services;

use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Support\PublicationCache;

/**
 * Lookup of published concrete agent overrides. The map is cached until an
 * override changes (see PublicationCache) and memoized per request — bound
 * as a scoped singleton so nothing leaks across Octane requests.
 */
final class ConcreteAgentOverrides
{
    /**
     * @var array<string, array{instructions: string|null, tools: list<string>|null}>|null
     */
    private ?array $overrides = null;

    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * The published prompt for the agent, or null to use its code prompt.
     */
    public function instructionsFor(string $agent): ?string
    {
        return $this->all()[$agent]['instructions'] ?? null;
    }

    /**
     * The overridden toolset for the agent, or null to use its code toolset.
     *
     * @return list<string>|null
     */
    public function toolsFor(string $agent): ?array
    {
        return $this->all()[$agent]['tools'] ?? null;
    }

    /**
     * @return array<string, array{instructions: string|null, tools: list<string>|null}>
     */
    private function all(): array
    {
        return $this->overrides ??= $this->load();
    }

    /**
     * Every override with a published prompt or a toolset, from the cache.
     *
     * @return array<string, array{instructions: string|null, tools: list<string>|null}>
     */
    private function load(): array
    {
        return $this->cache->remember(
            $this->cache->concreteAgentsKey(),
            fn (): array => ConcreteAgentOverrideModel::query()
                ->with('publishedVersion')
                ->get()
                ->mapWithKeys(fn (ConcreteAgentOverrideModel $override): array => [
                    $override->agent => [
                        'instructions' => ($content = (string) $override->publishedVersion?->content) === '' ? null : $content,
                        'tools' => $override->tools,
                    ],
                ])
                ->filter(fn (array $override): bool => $override['instructions'] !== null || $override['tools'] !== null)
                ->all(),
        );
    }
}
