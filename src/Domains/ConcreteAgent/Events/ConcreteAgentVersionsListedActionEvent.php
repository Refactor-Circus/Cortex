<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A concrete agent's prompt override versions were listed.
 */
final class ConcreteAgentVersionsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Collection<int, ConcreteAgentOverrideVersionModel>  $versions
     */
    public function __construct(
        public ConcreteAgentOverrideModel $override,
        public Collection $versions,
    ) {}
}
