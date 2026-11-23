<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * The summary of Cortex's settings is about to be shown.
 */
final class SettingsSummaryShowingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;
}
