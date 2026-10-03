<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Mcp\Requests;

use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Cortex\Mcp\Request;

abstract class VirtualAgentMcpRequest extends Request
{
    private ?VirtualAgentModel $agent = null;

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
