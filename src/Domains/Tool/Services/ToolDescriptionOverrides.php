<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Services;

use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Support\PublicationCache;

/**
 * Lookup of published tool description overrides. The map is cached until a
 * new version is published (see PublicationCache) and memoized per request —
 * bound as a scoped singleton so nothing leaks across Octane requests.
 */
final class ToolDescriptionOverrides
{
    /**
     * @var array<string, string>|null
     */
    private ?array $overrides = null;

    public function __construct(private readonly PublicationCache $cache) {}

    public function for(string $tool): ?string
    {
        return $this->all()[$tool] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function all(): array
    {
        /** @var array<string, string> */
        return $this->overrides ??= (array) $this->cache->remember(
            $this->cache->toolDescriptionsKey(),
            fn (): array => ToolDescriptionModel::query()
                ->whereNotNull('published_version_id')
                ->with('publishedVersion')
                ->get()
                ->mapWithKeys(fn (ToolDescriptionModel $description): array => [
                    $description->tool => (string) $description->publishedVersion?->content,
                ])
                ->filter(fn (string $content): bool => $content !== '')
                ->all(),
        );
    }
}
