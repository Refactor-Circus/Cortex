<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use JayI\Cortex\Mcp\Request;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;

abstract class VirtualAgentMcpRequest extends Request
{
    private ?VirtualAgent $agent = null;

    protected function agent(): VirtualAgent
    {
        return $this->agent ??= VirtualAgent::query()
            ->where('slug', $this->get('slug'))
            ->firstOrFail();
    }

    /**
     * The prompt version named in the input.
     */
    protected function version(): VirtualAgentVersion
    {
        /** @var VirtualAgentVersion */
        return $this->agent()->versions()->where('version', (int) $this->get('version'))->firstOrFail();
    }
}
