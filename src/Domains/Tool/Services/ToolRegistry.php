<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JayI\Cortex\Domains\Tool\Exceptions\ToolNotFoundException;
use JayI\Cortex\Domains\Tool\Support\DescribedTool;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\McpServerTool;
use Laravel\Ai\Tools\ToolNameResolver;
use Laravel\Mcp\Server\Tool as McpTool;

final class ToolRegistry
{
    /**
     * @var array<string, class-string<Tool>|class-string<McpTool>>
     */
    private array $tools = [];

    /**
     * Tags given explicitly at registration, keyed by tool name.
     *
     * @var array<string, list<string>>
     */
    private array $tags = [];

    private bool $configLoaded = false;

    public function __construct(private readonly Container $container) {}

    /**
     * @param  list<string>  $tags  Tags to group the tool under, on top of any
     *                              derived from its namespace.
     */
    public function register(string $name, string $class, array $tags = []): void
    {
        if (! is_a($class, Tool::class, true) && ! is_a($class, McpTool::class, true)) {
            throw new InvalidArgumentException(
                sprintf('Tool [%s] must implement %s or extend %s.', $class, Tool::class, McpTool::class),
            );
        }

        $this->loadConfigTools();

        $this->tools[$name] = $class;
        $this->tags[$name] = $this->normalizeTags($tags);
    }

    public function has(string $name): bool
    {
        $this->loadConfigTools();

        return array_key_exists($name, $this->tools);
    }

    public function get(string $name): Tool
    {
        if (! $this->has($name)) {
            throw ToolNotFoundException::forName($name);
        }

        $tool = $this->container->make($this->tools[$name]);

        if (! $tool instanceof Tool) {
            $tool = new McpServerTool($tool);
        }

        $override = $this->container->make(ToolDescriptionOverrides::class)->for($name);

        return $override === null ? $tool : new DescribedTool($tool, $override);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        $this->loadConfigTools();

        return array_keys($this->tools);
    }

    /**
     * A tool's tags: those given at registration plus those derived from its
     * class namespace (see `cortex.tool_tags.namespaces`), sorted.
     *
     * @return list<string>
     */
    public function tagsFor(string $name): array
    {
        if (! $this->has($name)) {
            throw ToolNotFoundException::forName($name);
        }

        $tags = array_values(array_unique([
            ...$this->tags[$name],
            ...$this->namespaceTags($this->tools[$name]),
        ]));

        sort($tags);

        return $tags;
    }

    /**
     * Every tag used by a registered tool, sorted.
     *
     * @return list<string>
     */
    public function tags(): array
    {
        $tags = array_values(array_unique(array_merge([], ...array_map($this->tagsFor(...), $this->names()))));

        sort($tags);

        return $tags;
    }

    /**
     * @return list<array{name: string, class: class-string<Tool>|class-string<McpTool>, description: string, schema: array<string, mixed>, tags: list<string>}>
     */
    public function all(?string $tag = null): array
    {
        $this->loadConfigTools();

        $names = $tag === null
            ? $this->names()
            : array_values(array_filter($this->names(), fn (string $name): bool => in_array($tag, $this->tagsFor($name), true)));

        return array_map(function (string $name): array {
            $tool = $this->get($name);

            return [
                'name' => $name,
                'class' => $this->tools[$name],
                'description' => (string) $tool->description(),
                'schema' => JsonSchema::object($tool->schema(...))->toArray(),
                'tags' => $this->tagsFor($name),
            ];
        }, $names);
    }

    private function loadConfigTools(): void
    {
        if ($this->configLoaded) {
            return;
        }

        $this->configLoaded = true;

        /** @var array<string|int, string|array{class: string, tags?: list<string>}> $configured */
        $configured = $this->container->make('config')->get('cortex.tools', []);

        foreach ($configured as $name => $entry) {
            $class = is_array($entry) ? $entry['class'] : $entry;
            $tags = is_array($entry) ? ($entry['tags'] ?? []) : [];

            $this->register(is_string($name) ? $name : $this->deriveName($class), $class, $tags);
        }
    }

    /**
     * Tags taken from the class namespace: each `cortex.tool_tags.namespaces`
     * pattern holds a `{tag}` placeholder standing for one namespace segment,
     * so `App\Domains\{tag}\` tags `App\Domains\Order\Mcp\ShowOrderTool`
     * as `order`.
     *
     * @return list<string>
     */
    private function namespaceTags(string $class): array
    {
        /** @var list<string> $patterns */
        $patterns = $this->container->make('config')->get('cortex.tool_tags.namespaces', []);

        $tags = [];

        foreach ($patterns as $pattern) {
            $regex = '/^'.str_replace(preg_quote('{tag}', '/'), '([^\\\\]+)', preg_quote(ltrim($pattern, '\\'), '/')).'/';

            if (preg_match($regex, ltrim($class, '\\'), $matches) === 1) {
                $tags[] = $matches[1];
            }
        }

        return $this->normalizeTags($tags);
    }

    /**
     * @param  array<int, string>  $tags
     * @return list<string>
     */
    private function normalizeTags(array $tags): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn (string $tag): string => Str::kebab(trim($tag)), $tags),
            fn (string $tag): bool => $tag !== '',
        )));
    }

    /**
     * Resolve a registration name for a list-style (unkeyed) config entry
     * from the tool's own name. Non-tool classes fall through so that
     * register() rejects them with its usual exception.
     */
    private function deriveName(string $class): string
    {
        if (! is_a($class, Tool::class, true) && ! is_a($class, McpTool::class, true)) {
            return $class;
        }

        $tool = $this->container->make($class);

        if ($tool instanceof McpTool) {
            return $tool->name();
        }

        return $tool instanceof Tool ? ToolNameResolver::resolve($tool) : $class;
    }
}
