<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Events;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use JayI\Foundation\Contracts\ActionFinishedEvent;

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
