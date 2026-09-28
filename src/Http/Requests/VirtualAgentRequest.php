<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use JayI\Cortex\Http\Request;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;

abstract class VirtualAgentRequest extends Request
{
    protected function agent(): VirtualAgent
    {
        $agent = $this->route('agent');

        if (! $agent instanceof VirtualAgent) {
            abort(404);
        }

        return $agent;
    }

    /**
     * The prompt version named in the route.
     */
    protected function version(): VirtualAgentVersion
    {
        /** @var VirtualAgentVersion */
        return $this->agent()->versions()->where('version', (int) $this->route('version'))->firstOrFail();
    }
}
