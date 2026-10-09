<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RefactorCircus\Cortex\Domains\McpServer\Exceptions\McpServerNotFoundException;
use RefactorCircus\Cortex\Mcp\CortexServer;
use Laravel\Mcp\Server as McpServer;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use ReflectionClass;

/**
 * Registry of MCP servers whose instructions Cortex can override. Server
 * classes are never instantiated here (their constructor requires a
 * transport) — names and default instructions are read via reflection.
 */
final class McpServerRegistry
{
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

        $this->servers['cortex'] = CortexServer::class;

        /** @var array<string|int, string> $configured */
        $configured = $this->container->make('config')->get('cortex.mcp.servers', []);

        foreach ($configured as $name => $class) {
            $this->register(is_string($name) ? $name : $this->deriveName($class), $class);
        }
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
