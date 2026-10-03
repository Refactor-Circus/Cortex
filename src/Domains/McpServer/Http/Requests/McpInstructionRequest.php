<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Http\Requests;

use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use JayI\Cortex\Domains\McpServer\Services\McpServerRegistry;
use JayI\Cortex\Http\Request;

abstract class McpInstructionRequest extends Request
{
    /**
     * The registered server name from the route, verified against the
     * registry. Named serverName() because Illuminate\Http\Request
     * already declares a public server() for the server params.
     */
    protected function serverName(): string
    {
        $server = $this->route('server');

        if (! is_string($server) || ! app(McpServerRegistry::class)->has($server)) {
            abort(404);
        }

        return $server;
    }

    protected function instruction(): McpInstructionModel
    {
        $instruction = McpInstructionModel::query()->where('server', $this->serverName())->first();

        if ($instruction === null) {
            abort(404);
        }

        return $instruction;
    }

    /**
     * The version named in the route.
     */
    protected function version(): McpInstructionVersionModel
    {
        /** @var McpInstructionVersionModel */
        return $this->instruction()->versions()->where('version', (int) $this->route('version'))->firstOrFail();
    }

    /**
     * The override for the server, or an unsaved one when no version exists yet, so
     * creating the first version is checked against the same policy.
     */
    protected function instructionOrNew(): McpInstructionModel
    {
        return McpInstructionModel::query()->firstOrNew(['server' => $this->serverName()]);
    }
}
