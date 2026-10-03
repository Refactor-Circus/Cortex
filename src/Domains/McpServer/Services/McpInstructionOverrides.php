<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Services;

use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Support\PublicationCache;

/**
 * Lookup of published MCP server instruction overrides. The map is cached
 * until a new version is published (see PublicationCache) and memoized per
 * request — bound as a scoped singleton so nothing leaks across Octane
 * requests.
 */
final class McpInstructionOverrides
{
    /**
     * @var array<string, string>|null
     */
    private ?array $overrides = null;

    public function __construct(private readonly PublicationCache $cache) {}

    public function for(string $server): ?string
    {
        return $this->all()[$server] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function all(): array
    {
        /** @var array<string, string> */
        return $this->overrides ??= (array) $this->cache->remember(
            $this->cache->mcpInstructionsKey(),
            fn (): array => McpInstructionModel::query()
                ->whereNotNull('published_version_id')
                ->with('publishedVersion')
                ->get()
                ->mapWithKeys(fn (McpInstructionModel $instruction): array => [
                    $instruction->server => (string) $instruction->publishedVersion?->content,
                ])
                ->filter(fn (string $content): bool => $content !== '')
                ->all(),
        );
    }
}
