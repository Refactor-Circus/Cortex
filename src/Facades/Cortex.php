<?php

declare(strict_types=1);

namespace JayI\Cortex\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \JayI\Cortex\Tools\ToolRegistry tools()
 * @method static \JayI\Cortex\Mcp\McpServerRegistry servers()
 * @method static \JayI\Cortex\Agents\AgentRegistry agents()
 * @method static \JayI\Cortex\Runtime\DbAgent virtualAgent(string $slug)
 * @method static \Laravel\Ai\Contracts\Agent concreteAgent(string $name)
 * @method static \Laravel\Ai\Responses\AgentResponse runVirtualAgent(string $slug, string $input)
 * @method static \Laravel\Ai\Responses\AgentResponse runConcreteAgent(string $name, string $input)
 *
 * @see \JayI\Cortex\Cortex
 */
class Cortex extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JayI\Cortex\Cortex::class;
    }
}
