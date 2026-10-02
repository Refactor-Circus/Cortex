<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use Illuminate\Http\RedirectResponse;
use JayI\Cortex\Actions\PublishVirtualAgentVersionAction;
use JayI\Cortex\Http\Ui\Concerns\AuthorizesScreens;
use JayI\Cortex\Models\VirtualAgent;

/**
 * New prompt versions are saved through the agent form; this only moves the
 * published pointer, so an earlier version can be restored.
 */
final class VirtualAgentVersionUiController
{
    use AuthorizesScreens;

    public function publish(VirtualAgent $agent, int $version): RedirectResponse
    {
        $this->authorizeScreen('publish', $agent->versions()->where('version', $version)->firstOrFail());

        app(PublishVirtualAgentVersionAction::class)->execute($agent, $version);

        return redirect()
            ->route('atrium.cortex.virtual-agents.edit', $agent->slug)
            ->with('status', __('cortex::cortex.version_published'));
    }
}
