<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\PublishVirtualAgentVersionAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

/**
 * New prompt versions are saved through the agent form; this only moves the
 * published pointer, so an earlier version can be restored.
 */
final class VirtualAgentVersionUiController
{
    use AuthorizesScreens;

    public function publish(VirtualAgentModel $agent, int $version): RedirectResponse
    {
        $this->authorizeScreen('publish', $agent->versions()->where('version', $version)->firstOrFail());

        app(PublishVirtualAgentVersionAction::class)->execute($agent, $version);

        return redirect()
            ->route('atrium.cortex.virtual-agents.edit', $agent->slug)
            ->with('status', __('cortex::cortex.version_published'));
    }
}
