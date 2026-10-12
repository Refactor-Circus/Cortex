<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Mcp\Request;

abstract class VirtualAgentMcpRequest extends Request
{
    private ?VirtualAgentModel $agent = null;

    /**
     * The input naming the virtual agent.
     *
     * @return array<string, array<int, string>>
     */
    protected function agentRules(): array
    {
        return [
            'slug' => ['required', 'string'],
        ];
    }

    /**
     * The input naming the virtual agent and one of its prompt versions.
     *
     * @return array<string, array<int, string>>
     */
    protected function versionRules(): array
    {
        return $this->agentRules() + [
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function agent(): VirtualAgentModel
    {
        return $this->agent ??= VirtualAgentModel::query()
            ->where('slug', $this->get('slug'))
            ->firstOrFail();
    }

    /**
     * The prompt version named in the input.
     */
    protected function version(): VirtualAgentVersionModel
    {
        /** @var VirtualAgentVersionModel */
        return $this->agent()->versions()->where('version', (int) $this->get('version'))->firstOrFail();
    }
}
