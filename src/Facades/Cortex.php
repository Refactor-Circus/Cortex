<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry tools()
 * @method static \RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry servers()
 * @method static \RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry agents()
 * @method static \RefactorCircus\Cortex\Domains\VirtualAgent\Support\DbAgent virtualAgent(string $slug)
 * @method static \Laravel\Ai\Contracts\Agent concreteAgent(string $name)
 * @method static \Laravel\Ai\Responses\AgentResponse runVirtualAgent(string $slug, string $input)
 * @method static \Laravel\Ai\Responses\AgentResponse runConcreteAgent(string $name, string $input)
 *
 * @see \RefactorCircus\Cortex\Cortex
 */
class Cortex extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RefactorCircus\Cortex\Cortex::class;
    }
}
