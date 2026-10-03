<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionStartingEvent;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

/**
 * A new version of a virtual agent's prompt is about to be created.
 */
final class VirtualAgentVersionCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public VirtualAgentModel $agent,
        public array $data,
    ) {}
}
