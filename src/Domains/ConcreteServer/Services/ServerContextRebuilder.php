<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Services;

use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;
use ReflectionClass;
use ReflectionProperty;

/**
 * Reads and replaces the tools of a laravel/mcp `ServerContext`, which keeps
 * them in a protected `$tools` and takes them only through its constructor.
 *
 * Both depend on laravel/mcp internals: every constructor parameter backed
 * by a promoted property of the same name, and `$tools` an array.
 * `tests/Feature/Mcp/ServerContextPinTest.php` pins them. When they change,
 * the rebuild throws (a ReflectionException, an Error for an unknown or
 * missing parameter) and serving falls back to the code tools (D-028).
 */
final class ServerContextRebuilder
{
    /**
     * The tools the context was built with, as the server declared them:
     * tool instances or class names, ToolSearch groups under their key.
     *
     * @return array<int|string, mixed>
     */
    public function tools(ServerContext $context): array
    {
        $tools = (new ReflectionProperty($context, 'tools'))->getValue($context);

        return is_array($tools) ? $tools : [];
    }

    /**
     * A copy of the context serving exactly these tools, in this order, with
     * everything else (instructions, capabilities, resources, prompts) kept.
     *
     * @param  list<Tool>  $tools
     */
    public function withTools(ServerContext $context, array $tools): ServerContext
    {
        $class = new ReflectionClass($context);
        $arguments = [];

        foreach ($class->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();

            $arguments[$name] = (new ReflectionProperty($context, $name))->getValue($context);
        }

        // Passed by name whether or not the constructor still takes it: a
        // constructor without `tools` fails here instead of serving the code
        // tools silently.
        $arguments['tools'] = $tools;

        /** @var ServerContext */
        return $class->newInstanceArgs($arguments);
    }
}
