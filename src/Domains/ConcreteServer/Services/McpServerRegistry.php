<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Mcp\Server as McpServer;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool as McpTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Exceptions\McpServerNotFoundException;
use RefactorCircus\Cortex\Mcp\CortexServer;
use ReflectionClass;

/**
 * Registry of MCP servers whose instructions and tools Cortex can override.
 * Server classes are never instantiated here (their constructor requires a
 * transport) — names, default instructions and default tools are read via
 * reflection.
 */
final class McpServerRegistry
{
    /**
     * The name Cortex's own server is always registered under.
     */
    private const string CORTEX = 'cortex';

    /**
     * @var array<string, class-string<McpServer>>
     */
    private array $servers = [];

    private bool $configLoaded = false;

    public function __construct(private readonly Container $container) {}

    public function register(string $name, string $class): void
    {
        if (! is_a($class, McpServer::class, true)) {
            throw new InvalidArgumentException(
                sprintf('MCP server [%s] must extend %s.', $class, McpServer::class),
            );
        }

        $this->loadConfigServers();

        $this->servers[$name] = $class;
    }

    public function has(string $name): bool
    {
        $this->loadConfigServers();

        return array_key_exists($name, $this->servers);
    }

    /**
     * @return class-string<McpServer>
     */
    public function get(string $name): string
    {
        if (! $this->has($name)) {
            throw McpServerNotFoundException::forName($name);
        }

        return $this->servers[$name];
    }

    /**
     * The registered name for a server class, or null when unregistered.
     * A class registered under several names resolves to the first one.
     */
    public function nameFor(string $class): ?string
    {
        $this->loadConfigServers();

        $name = array_search($class, $this->servers, true);

        return $name === false ? null : $name;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        $this->loadConfigServers();

        return array_keys($this->servers);
    }

    /**
     * The instructions the server class declares in code, before any
     * published override is applied.
     */
    public function defaultInstructions(string $name): string
    {
        $reflection = new ReflectionClass($this->get($name));

        $current = $reflection;

        do {
            $attributes = $current->getAttributes(Instructions::class);

            if ($attributes !== []) {
                return $attributes[0]->newInstance()->value;
            }
        } while ($current = $current->getParentClass());

        $default = $reflection->getProperty('instructions')->getDefaultValue();

        return is_string($default) ? $default : '';
    }

    /**
     * The names of the tools the server class declares in code, in
     * declaration order, before any tool override is applied. Read from the
     * class's `$tools` default without building the server: ToolSearch
     * groups are flattened and each tool is named by its `name()`.
     *
     * Tools a server adds at runtime (in its constructor or `boot()`) are
     * not seen here; serving builds the code tools from the server itself.
     *
     * @return list<string>
     */
    public function defaultTools(string $name): array
    {
        $names = [];

        foreach ($this->declaredTools($name) as $entry) {
            foreach (is_array($entry) ? $entry : [$entry] as $tool) {
                $names[] = $this->toolName($tool);
            }
        }

        return $names;
    }

    /**
     * The server class's `$tools` default as declared (classes, instances
     * and ToolSearch groups), read without building the server.
     *
     * @return array<int|string, mixed>
     */
    public function declaredTools(string $name): array
    {
        $default = (new ReflectionClass($this->get($name)))->getProperty('tools')->getDefaultValue();

        return is_array($default) ? $default : [];
    }

    /**
     * Whether Cortex may override the server's tool list. Every registered
     * server may except Cortex's own, by its registered name: the same class
     * registered under another name is overridable.
     */
    public function supportsToolOverrides(string $name): bool
    {
        $this->get($name);

        return $name !== self::CORTEX;
    }

    /**
     * @return list<array{name: string, class: class-string<McpServer>, instructions: string}>
     */
    public function all(): array
    {
        $overrides = $this->container->make(McpInstructionOverrides::class);

        return array_map(fn (string $name): array => [
            'name' => $name,
            'class' => $this->servers[$name],
            'instructions' => $overrides->for($name) ?? $this->defaultInstructions($name),
        ], $this->names());
    }

    private function loadConfigServers(): void
    {
        if ($this->configLoaded) {
            return;
        }

        $this->configLoaded = true;

        $this->servers[self::CORTEX] = CortexServer::class;

        /** @var array<string|int, string> $configured */
        $configured = $this->container->make('config')->get('cortex.mcp.servers', []);

        foreach ($configured as $name => $class) {
            $this->register(is_string($name) ? $name : $this->deriveName($class), $class);
        }
    }

    private function toolName(mixed $tool): string
    {
        if (is_string($tool) && is_a($tool, McpTool::class, true)) {
            $tool = $this->container->make($tool);
        }

        return $tool instanceof McpTool ? $tool->name() : (is_string($tool) ? $tool : get_debug_type($tool));
    }

    /**
     * Resolve a registration name for a list-style (unkeyed) config entry
     * from the server's #[Name] attribute, falling back to the class
     * basename. Non-server classes fall through so that register()
     * rejects them with its usual exception.
     */
    private function deriveName(string $class): string
    {
        if (! is_a($class, McpServer::class, true)) {
            return $class;
        }

        $attributes = (new ReflectionClass($class))->getAttributes(Name::class);

        if ($attributes !== []) {
            return Str::slug($attributes[0]->newInstance()->value);
        }

        return Str::kebab(class_basename($class));
    }
}
