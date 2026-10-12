<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\McpServerTool;
use Laravel\Ai\Tools\ToolNameResolver;
use Laravel\Mcp\Server\Tool as McpTool;
use RefactorCircus\Cortex\Domains\Tool\Exceptions\ToolNotFoundException;
use RefactorCircus\Cortex\Domains\Tool\Support\DescribedTool;

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

    /**
     * Each tool's resolved tags, keyed by tool name; cleared on register()
     * and whenever `cortex.tool_tags.namespaces` changes.
     *
     * @var array<string, list<string>>
     */
    private array $resolvedTags = [];

    /**
     * The namespace patterns the resolved tags were built from.
     *
     * @var list<string>|null
     */
    private ?array $tagPatterns = null;

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
        $this->resolvedTags = [];
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

        /** @var list<string> $patterns */
        $patterns = $this->container->make('config')->get('cortex.tool_tags.namespaces', []);

        if ($this->tagPatterns !== $patterns) {
            $this->tagPatterns = $patterns;
            $this->resolvedTags = [];
        }

        if (isset($this->resolvedTags[$name])) {
            return $this->resolvedTags[$name];
        }

        $tags = array_values(array_unique([
            ...$this->tags[$name],
            ...$this->namespaceTags($this->tools[$name], $patterns),
        ]));

        sort($tags);

        return $this->resolvedTags[$name] = $tags;
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
     * The registered tools by name, class and tags only: no tool is
     * instantiated and no schema built, for lists that show nothing more.
     *
     * @return list<array{name: string, class: class-string<Tool>|class-string<McpTool>, tags: list<string>}>
     */
    public function summaries(?string $tag = null): array
    {
        return array_map(fn (string $name): array => [
            'name' => $name,
            'class' => $this->tools[$name],
            'tags' => $this->tagsFor($name),
        ], $this->namesTagged($tag));
    }

    /**
     * @return list<array{name: string, class: class-string<Tool>|class-string<McpTool>, description: string, schema: array<string, mixed>, tags: list<string>}>
     */
    public function all(?string $tag = null): array
    {
        return array_map(function (string $name): array {
            $tool = $this->get($name);

            return [
                'name' => $name,
                'class' => $this->tools[$name],
                'description' => (string) $tool->description(),
                'schema' => JsonSchema::object($tool->schema(...))->toArray(),
                'tags' => $this->tagsFor($name),
            ];
        }, $this->namesTagged($tag));
    }

    /**
     * @return list<string>
     */
    private function namesTagged(?string $tag): array
    {
        $names = $this->names();

        return $tag === null
            ? $names
            : array_values(array_filter($names, fn (string $name): bool => in_array($tag, $this->tagsFor($name), true)));
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
     * @param  list<string>  $patterns
     * @return list<string>
     */
    private function namespaceTags(string $class, array $patterns): array
    {
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
