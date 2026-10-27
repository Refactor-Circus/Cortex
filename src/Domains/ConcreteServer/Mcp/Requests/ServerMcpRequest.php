<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Mcp\Request;

abstract class ServerMcpRequest extends Request
{
    private ?McpInstructionModel $instruction = null;

    /**
     * The input naming the MCP server.
     *
     * @return array<string, array<int, string>>
     */
    protected function serverRules(): array
    {
        return [
            'server' => ['required', 'string'],
        ];
    }

    /**
     * The input naming the MCP server and one of its instruction versions.
     *
     * @return array<string, array<int, string>>
     */
    protected function versionRules(): array
    {
        return $this->serverRules() + [
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * The registered server name from the tool input, verified against the
     * registry. Unknown names surface as the base request's not-found error.
     */
    protected function serverName(): string
    {
        $server = (string) $this->get('server');

        if (! app(McpServerRegistry::class)->has($server)) {
            throw (new ModelNotFoundException)->setModel(McpInstructionModel::class);
        }

        return $server;
    }

    protected function instruction(): McpInstructionModel
    {
        return $this->instruction ??= McpInstructionModel::query()
            ->where('server', $this->serverName())
            ->firstOrFail();
    }

    /**
     * The version named in the input.
     */
    protected function version(): McpInstructionVersionModel
    {
        /** @var McpInstructionVersionModel */
        return $this->instruction()->versions()->where('version', (int) $this->get('version'))->firstOrFail();
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
