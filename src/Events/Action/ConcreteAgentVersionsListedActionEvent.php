<?php

declare(strict_types=1);

namespace JayI\Cortex\Events\Action;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

/**
 * A concrete agent's prompt override versions were listed.
 */
final class ConcreteAgentVersionsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Collection<int, ConcreteAgentOverrideVersion>  $versions
     */
    public function __construct(
        public ConcreteAgentOverride $override,
        public Collection $versions,
    ) {}
}
