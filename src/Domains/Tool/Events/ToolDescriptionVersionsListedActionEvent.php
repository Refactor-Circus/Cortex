<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Events;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Foundation\Contracts\ActionFinishedEvent;

/**
 * The versions of a tool's description override were listed.
 */
final class ToolDescriptionVersionsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Collection<int, ToolDescriptionVersionModel>  $versions
     */
    public function __construct(
        public ToolDescriptionModel $description,
        public Collection $versions,
    ) {}
}
