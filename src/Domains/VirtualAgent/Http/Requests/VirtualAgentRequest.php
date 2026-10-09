<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Http\Request;

abstract class VirtualAgentRequest extends Request
{
    protected function agent(): VirtualAgentModel
    {
        $agent = $this->route('agent');

        if (! $agent instanceof VirtualAgentModel) {
            abort(404);
        }

        return $agent;
    }

    /**
     * The prompt version named in the route.
     */
    protected function version(): VirtualAgentVersionModel
    {
        /** @var VirtualAgentVersionModel */
        return $this->agent()->versions()->where('version', (int) $this->route('version'))->firstOrFail();
    }
}
